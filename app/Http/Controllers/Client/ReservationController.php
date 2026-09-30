<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Mail\ReservationMail;
use App\Models\ModeReglement;
use App\Models\Reservation;
use App\Models\Voyage;
use App\Support\Cmi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    /**
     * Booking page of a voyage (seat map + payment mode).
     */
    public function index(Request $request, Voyage $voyage)
    {
        // seats of card payments abandoned for too long become free again
        Reservation::expirePendingPayments();

        $voyage->load(['autocar.societe', 'autocar.equipements', 'autocar.options', 'villeDepart', 'villeArrivee', 'typeVoyage', 'arrets.ville']);

        // the trip asked for: stop ids (?de=&a=, from the booking page) or city ids (?from=&to=, from the search)
        $segment = $request->filled('de')
            ? $voyage->segmentByIds($request->integer('de'), $request->integer('a'))
            : $voyage->segmentFor($request->integer('from') ?: null, $request->integer('to') ?: null);
        $segment ??= $voyage->segmentFor();

        if (! $segment || $segment[0]->passage_at->isPast()) {
            return redirect()->route('voyages.list')->with('error', __('Ce voyage est déjà parti. Choisissez un autre départ.'));
        }

        [$depart, $arrivee] = $segment;
        $prix = $voyage->segmentPrice($depart, $arrivee);
        $reservedSeats = $voyage->seatsTaken($depart, $arrivee);
        $equipements = $voyage->autocar?->equipements ?? collect();
        $modes = self::availableModes();

        return view('client.reservations.index', compact('voyage', 'equipements', 'modes', 'depart', 'arrivee', 'prix', 'reservedSeats'));
    }

    /**
     * Old entry point kept for existing links: /client/create/reservation?id={voyage}
     */
    public function create(Request $request)
    {
        if ($request->filled('id')) {
            return redirect()->route('client.reservations.show', $request->integer('id'));
        }

        return redirect()->route('voyages.list');
    }

    /**
     * Seat map → payment step: the chosen seats are checked and kept in the session (not held yet),
     * then the payment page shows the order and the payment choice.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'voyage_id'        => ['required', 'integer', 'exists:voyages,id'],
            'arret_depart_id'  => ['nullable', 'integer'],
            'arret_arrivee_id' => ['nullable', 'integer'],
            'seats'            => ['required', 'array', 'min:1', 'max:' . config('safar.max_sieges')],
            'seats.*'          => ['required', 'integer', 'min:1', 'distinct'],
            'retour_de'        => ['nullable', 'string', 'max:20'],
        ], [
            'seats.required'   => __('Veuillez choisir au moins un siège.'),
            'seats.max'        => __('Vous pouvez réserver au maximum :max sièges à la fois.', ['max' => config('safar.max_sieges')]),
            'seats.*.distinct' => __('Vous avez choisi deux fois le même siège.'),
        ]);

        $request->session()->put('panier', [
            'voyage_id' => $request->integer('voyage_id'),
            'de' => $request->integer('arret_depart_id') ?: null,
            'a' => $request->integer('arret_arrivee_id') ?: null,
            'seats' => collect($request->seats)->map(fn ($s) => (int) $s)->sort()->values()->all(),
            // return trip of an outbound order: discount checked again when booking
            'retour_de' => $request->input('retour_de'),
        ]);

        return redirect()->route('client.reservations.payment');
    }

    /** Payment step: summary of the order + payment choice (card online, or pay at boarding). */
    public function payment(Request $request)
    {
        $panier = $request->session()->get('panier');
        if (! $panier) {
            return redirect()->route('voyages.list');
        }

        Reservation::expirePendingPayments();
        $voyage = Voyage::with(['autocar.societe', 'arrets.ville'])->find($panier['voyage_id']);
        $segment = $voyage ? ($panier['de'] ? $voyage->segmentByIds($panier['de'], $panier['a']) : $voyage->segmentFor()) : null;
        if (! $segment || $segment[0]->passage_at->isPast()) {
            $request->session()->forget('panier');

            return redirect()->route('voyages.list')->with('error', __('Ce voyage est déjà parti. Choisissez un autre départ.'));
        }
        [$depart, $arrivee] = $segment;

        // someone else took a seat meanwhile: back to the seat map
        $pris = collect($panier['seats'])->intersect($voyage->seatsTaken($depart, $arrivee));
        if ($pris->isNotEmpty()) {
            return redirect()->route('client.reservations.show', ['voyage' => $voyage, 'de' => $depart->id, 'a' => $arrivee->id])
                ->with('error', trans_choice('{1} Le siège :seats vient d\'être réservé par un autre client. Veuillez en choisir un autre.|[2,*] Les sièges :seats viennent d\'être réservés par d\'autres clients. Veuillez en choisir d\'autres.', $pris->count(), ['seats' => $pris->join(', ')]));
        }

        $prix = $voyage->segmentPrice($depart, $arrivee);
        $seats = $panier['seats'];
        $modes = self::availableModes($request->user());
        $cashRefused = ! $request->user()->mayPayAtBoarding();
        $quotaAtteint = ! $request->user()->mayBookUnpaid();
        // return trip of an outbound order: discount shown now, applied when booking
        $retourDe = Reservation::retourValide($panier['retour_de'] ?? null, $request->user(), $depart);
        $remiseRetour = $retourDe ? round($prix * count($seats) * (float) config('safar.remise_retour_pourcent') / 100, 2) : 0.0;

        return view('client.reservations.paiement', compact('voyage', 'depart', 'arrivee', 'prix', 'seats', 'modes', 'cashRefused', 'quotaAtteint', 'retourDe', 'remiseRetour'));
    }

    /** Payment page "Appliquer": checks a promo code on the current cart (the booking checks it again). */
    public function promo(Request $request)
    {
        $panier = $request->session()->get('panier');
        $voyage = $panier ? Voyage::with('arrets')->find($panier['voyage_id']) : null;
        $segment = $voyage ? ($panier['de'] ? $voyage->segmentByIds($panier['de'], $panier['a']) : $voyage->segmentFor()) : null;
        abort_unless($segment, 404);

        $total = $voyage->segmentPrice(...$segment) * count($panier['seats']);
        [$promotion, $remise, $refus] = \App\Models\Promotion::appliquer($request->input('code'), $total);

        return response()->json($refus
            ? ['ok' => false, 'message' => $refus]
            : ['ok' => (bool) $promotion, 'remise' => $remise, 'total' => round($total - $remise, 2), 'libelle' => $promotion?->libelle(),
               'message' => $promotion ? __('Code :code appliqué : :libelle.', ['code' => $promotion->code, 'libelle' => $promotion->libelle()]) : null]);
    }

    /** All the tickets of an order (after booking or after the card payment). */
    public function order(Request $request, string $commande)
    {
        $billets = $this->ownedOrder($commande);

        return view('client.reservations.commande', ['billets' => $billets, 'commande' => $commande]);
    }

    /** One PDF with every ticket of the order (one page + QR code per seat). */
    public function downloadOrder(string $commande)
    {
        $billets = $this->ownedOrder($commande)->reject->isCancelled()->values();
        abort_if($billets->isEmpty(), 404, __('Ce billet a été annulé.'));

        return Pdf::loadView('client.reservations.ticket-pdf', ['reservations' => $billets])
            ->setPaper('a5', 'landscape')
            ->download('billets-' . $commande . '.pdf');
    }

    private function ownedOrder(string $commande)
    {
        $billets = Reservation::withoutGlobalScope('active')
            ->with(['user', 'villeDepart', 'villeArrivee', 'modeReglement', 'autocar.societe'])
            ->where('commande', $commande)->orderBy('num_siege')->get();
        abort_if($billets->isEmpty(), 404);
        abort_unless($billets->first()->user_id === auth()->id(), 403);

        return $billets;
    }

    public function store(Request $request)
    {
        $request->validate([
            'voyage_id'         => ['required', 'integer', 'exists:voyages,id'],
            'arret_depart_id'   => ['nullable', 'integer'],
            'arret_arrivee_id'  => ['nullable', 'integer'],
            'seats'             => ['required', 'array', 'min:1', 'max:' . config('safar.max_sieges')],
            'seats.*'           => ['required', 'integer', 'min:1', 'distinct'],
            // traveller of each seat, keyed by seat number (optional: the account holder by default)
            'passagers'         => ['nullable', 'array'],
            'passagers.*'       => ['nullable', 'string', 'max:120'],
            'code_promo'        => ['nullable', 'string', 'max:30'],
            // return trip: order of the outbound journey (return-trip discount, see Reservation::remiseRetour)
            'retour_de'         => ['nullable', 'string', 'max:20'],
            'mode_reglement_id' => ['required', 'integer', 'in:' . self::availableModes($request->user())->pluck('id')->join(',')],
        ], [
            'seats.required'             => __('Veuillez choisir au moins un siège.'),
            'seats.max'                  => __('Vous pouvez réserver au maximum :max sièges à la fois.', ['max' => config('safar.max_sieges')]),
            'seats.*.distinct'           => __('Vous avez choisi deux fois le même siège.'),
            'mode_reglement_id.required' => __('Veuillez choisir un mode de règlement.'),
            'mode_reglement_id.in'       => __('Le mode de règlement choisi est invalide.'),
        ]);

        $seats = collect($request->seats)->map(fn ($seat) => (int) $seat)->sort()->values();
        $mode = ModeReglement::findOrFail($request->mode_reglement_id);
        $online = (bool) $mode->en_ligne;
        $agence = ! $online && (bool) $mode->en_agence;
        Reservation::expirePendingPayments();

        // unpaid seats are capped per account (config safar.max_non_payes): more seats = pay by card
        $maxUnpaid = (int) config('safar.max_non_payes');
        if (! $online && $maxUnpaid > 0 && $request->user()->siegesNonPayesAVenir() + $seats->count() > $maxUnpaid) {
            return back()->withInput()->with('error', __('Vous avez déjà :held siège(s) non payé(s) sur vos prochains voyages. Sans paiement, :max sièges au maximum : payez par carte pour réserver davantage.', ['held' => $request->user()->siegesNonPayesAVenir(), 'max' => $maxUnpaid]));
        }

        try {
            // one ticket per seat, all in the same order: all booked, or none
            $noms = collect((array) $request->input('passagers', []))->map(fn ($n) => trim((string) $n))->filter();
            $billets = DB::transaction(function () use ($request, $seats, $online, $agence, $noms) {
                // Lock the voyage row so two clients cannot book the same seat at the same time
                $voyage = Voyage::with(['autocar', 'arrets'])->lockForUpdate()->findOrFail($request->voyage_id);

                $segment = $request->filled('arret_depart_id')
                    ? $voyage->segmentByIds($request->arret_depart_id, $request->arret_arrivee_id)
                    : $voyage->segmentFor();
                if (! $segment) {
                    throw new \DomainException(__('Ce trajet n\'est pas proposé par ce voyage.'));
                }
                [$depart, $arrivee] = $segment;

                if ($depart->passage_at->isPast()) {
                    throw new \DomainException(__('Ce voyage est déjà parti, la réservation est impossible.'));
                }

                if (! $voyage->autocar || $seats->max() > $voyage->autocar->nbr_siege) {
                    throw new \DomainException(__("Le siège choisi n'existe pas dans cet autocar."));
                }

                // taken = sold on at least part of this segment (cancelled tickets are ignored by the global scope)
                $pris = $seats->intersect($voyage->seatsTaken($depart, $arrivee));
                if ($pris->isNotEmpty()) {
                    throw new \DomainException(trans_choice('{1} Le siège :seats vient d\'être réservé par un autre client. Veuillez en choisir un autre.|[2,*] Les sièges :seats viennent d\'être réservés par d\'autres clients. Veuillez en choisir d\'autres.', $pris->count(), ['seats' => $pris->join(', ')]));
                }

                $commande = Reservation::nouvelleCommande();

                // discount of the order: promo code or return-trip discount (the better one), split between the seats
                $prix = $voyage->segmentPrice($depart, $arrivee);
                $total = $prix * $seats->count();
                [$promotion, $remisePromo, $refus] = \App\Models\Promotion::appliquer($request->input('code_promo'), $total);
                if ($refus) {
                    throw new \DomainException($refus);
                }
                $retourDe = Reservation::retourValide($request->input('retour_de'), $request->user(), $depart);
                $remiseRetour = $retourDe ? round($total * (float) config('safar.remise_retour_pourcent') / 100, 2) : 0.0;
                if ($remiseRetour > $remisePromo) {
                    $promotion = null;
                }
                $remises = Reservation::repartir(max($remisePromo, $remiseRetour), $seats->count());
                $promotion?->increment('utilisations');

                return $seats->values()->map(fn ($seat, $i) => Reservation::create([
                    'commande'          => $commande,
                    'retour_de'         => $retourDe,
                    'promotion_id'      => $promotion?->id,
                    'remise'            => $remises[$i],
                    'num_siege'         => $seat,
                    'passager_nom'      => $noms->get($seat) ?: $request->user()->name,
                    // card payment: seat held until CMI confirms; otherwise confirmed, paid at boarding
                    'statut'            => $online ? Reservation::EN_ATTENTE : Reservation::CONFIRMEE,
                    'user_id'           => $request->user()->id,
                    'mode_reglement_id' => $request->mode_reglement_id,
                    'date_reservation'  => now(),
                    // the ticket covers the client's segment only
                    'date_depart'       => $depart->passage_at->toDateString(),
                    'date_arrivee'      => $arrivee->passage_at->toDateString(),
                    'heure_depart'      => $depart->passage_at->format('H:i:s'),
                    'heure_arrivee'     => $arrivee->passage_at->format('H:i:s'),
                    'ville_depart_id'   => $depart->ville_id,
                    'ville_arrivee_id'  => $arrivee->ville_id,
                    'autocar_id'        => $voyage->autocar_id,
                    'type_voyage_id'    => $voyage->type_voyage_id,
                    'prix'              => $prix,
                    'frais'             => 0,
                    'voyage_id'         => $voyage->id,
                    'arret_depart_id'   => $depart->id,
                    'arret_arrivee_id'  => $arrivee->id,
                    // agency payment has its own deadline (reservations:agence): no presence e-mail either
                    'presence_confirmee_le' => ! $online && $agence ? now() : null,
                ]));
            });
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('error', __('Une erreur est survenue lors de la réservation. Veuillez réessayer.'));
        }

        $reservation = $billets->first();
        $request->session()->forget(['panier', 'retour_de']);

        if ($online) {
            // one card payment for the whole order
            return redirect()->route('payment.cmi.start', $reservation);
        }

        // one e-mail with every ticket of the order (one PDF page + QR code per seat)
        ReservationMail::sendTo($billets, 'confirmee');

        // every ticket of the order on one page (not only the first one)
        return redirect()->route('client.commande.show', $reservation->commande)
            ->with('success', trans_choice('{1} Réservation confirmée ! Votre billet vous a aussi été envoyé par e-mail.|[2,*] Réservation confirmée ! Vos :count billets vous ont aussi été envoyés par e-mail.', $billets->count()));
    }

    /**
     * Page opened by the QR code of a ticket (signed link): lets the driver or agent check it at boarding.
     */
    public function verify(Reservation $reservation)
    {
        $reservation->loadMissing(['user', 'villeDepart', 'villeArrivee', 'modeReglement', 'autocar.societe', 'embarquePar']);

        // staff opening the QR link with their phone camera: the scan counts like one from the scanner page
        $user = auth()->user();
        $controleur = $user?->isadmin && ($user->hasPermission('reservations.read') || $user->hasPermission('scanner.use'))
            && (! $user->societe_id || $reservation->autocar?->societe_id === $user->societe_id);
        if ($controleur) {
            \App\Support\Controle::noter($reservation, $user);
        }

        return view('client.reservations.verifier', compact('reservation', 'controleur'));
    }

    /** Full bus: "warn me when a seat frees up" (guests too: the e-mail is enough). */
    public function alerte(Request $request, Voyage $voyage)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:120'],
            'de' => ['nullable', 'integer'],
            'a' => ['nullable', 'integer'],
        ], ['email.required' => __('Indiquez l\'adresse e-mail à prévenir.')]);

        if ($voyage->departAt()->isPast()) {
            return back()->with('error', __('Ce voyage est déjà parti. Choisissez un autre départ.'));
        }

        \App\Models\AlertePlace::updateOrCreate(
            ['voyage_id' => $voyage->id, 'email' => strtolower($data['email'])],
            ['user_id' => $request->user()?->id, 'arret_depart_id' => $data['de'] ?? null, 'arret_arrivee_id' => $data['a'] ?? null, 'envoyee_le' => null],
        );

        return back()->with('success', __('C\'est noté : nous vous écrirons à :email dès qu\'une place se libère sur ce bus.', ['email' => $data['email']]));
    }

    /** PDF of a ticket opened from the link shared on WhatsApp (signed, temporary: no login needed). */
    public function sharedPdf(Reservation $reservation)
    {
        abort_if($reservation->isCancelled(), 410, __('Ce billet a été annulé.'));
        $reservation->loadMissing(['user', 'villeDepart', 'villeArrivee', 'modeReglement', 'autocar.societe']);

        return Pdf::loadView('client.reservations.ticket-pdf', compact('reservation'))
            ->setPaper('a5', 'landscape')
            ->stream('billet-' . $reservation->id . '.pdf');
    }

    /** "I'm coming" button of the presence e-mail (signed link, no login needed): the unpaid ticket is kept. */
    public function presence(Reservation $reservation)
    {
        $etat = match (true) {
            $reservation->isCancelled() => 'annule',
            $reservation->departAt()->isPast() => 'parti',
            default => 'ok',
        };
        if ($etat === 'ok' && $reservation->presence_confirmee_le === null) {
            $reservation->forceFill(['presence_confirmee_le' => now()])->save();
        }
        $reservation->loadMissing(['villeDepart', 'villeArrivee']);

        return view('client.reservations.presence', compact('reservation', 'etat'));
    }

    public function show($id)
    {
        $reservation = $this->findOwnedReservation($id);

        return view('client.reservations.ticket', compact('reservation'));
    }

    public function download($id)
    {
        $reservation = $this->findOwnedReservation($id);
        abort_if($reservation->isCancelled(), 404, __('Ce billet a été annulé.'));

        $pdf = Pdf::loadView('client.reservations.ticket-pdf', compact('reservation'))
            ->setPaper('a5', 'landscape');

        return $pdf->download('ticket-' . $reservation->id . '.pdf');
    }

    /**
     * The client cancels their own ticket (until SAFAR_ANNULATION_HEURES hours before departure).
     */
    public function cancel(Reservation $reservation)
    {
        abort_unless($reservation->user_id === auth()->id(), 403);

        if (! $reservation->canBeCancelledByClient()) {
            return back()->with('error', $reservation->isCancelled()
                ? __('Ce billet est déjà annulé.')
                : __('Le bus est déjà parti : ce billet ne peut plus être annulé.'));
        }

        $reservation->cancel('client');
        ReservationMail::sendTo($reservation, 'annulee');

        if (! $reservation->isPaid()) {
            return redirect()->route('client.profile.reservations.index')->with('success', __('Votre billet est annulé et le siège a été libéré.'));
        }

        return redirect()->route('client.profile.reservations.index')->with('success', (float) $reservation->montant_rembourse > 0
            ? __('Votre billet est annulé. Vous serez remboursé de :montant DH : notre équipe traite le remboursement.', [
                'montant' => number_format($reservation->montant_rembourse, 2, ',', ' '),
            ])
            : __('Votre billet est annulé. Aucun remboursement n\'est prévu à ce délai du départ.'));
    }

    /** Payment modes offered to clients: card payment only when CMI is configured (or the local test page). */
    public static function availableModes(?\App\Models\User $user = null)
    {
        // too many no-shows (config safar.absences_max): "pay at boarding" is no longer offered, card only
        $cashAllowed = ! $user || $user->mayPayAtBoarding();
        // monthly quota of orders booked without paying (config safar.non_payes_par_mois): then card only
        $unpaidAllowed = ! $user || $user->mayBookUnpaid();

        return ModeReglement::orderBy('mode_reglement')->get()
            // agency payment is paid before the trip: still offered after no-shows
            ->filter(fn ($mode) => $mode->en_ligne ? Cmi::available() : ($unpaidAllowed && ($mode->en_agence || $cashAllowed)))
            ->values();
    }

    /**
     * A client may only see their own tickets (cancelled ones included); admins with the permission may see all of them.
     */
    private function findOwnedReservation($id): Reservation
    {
        $reservation = Reservation::withoutGlobalScope('active')
            ->with(['user', 'villeDepart', 'villeArrivee', 'modeReglement', 'autocar.societe'])
            ->findOrFail($id);

        $user = auth()->user();
        abort_unless($reservation->user_id === $user->id || ($user->isadmin && ($user->hasPermission('reservations.read') || $user->hasPermission('scanner.use'))), 403);

        return $reservation;
    }
}

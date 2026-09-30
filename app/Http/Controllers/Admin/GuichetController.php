<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ReservationMail;
use App\Models\Encaissement;
use App\Models\ModeReglement;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Ville;
use App\Models\Voyage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Counter sales (Guichet), on any laptop or tablet: departure -> seats -> traveller -> paid now (cash or card)
 * -> tickets printed, sent by e-mail and/or WhatsApp. The seller is recorded on each ticket (vendu_par)
 * and on the payment (encaissements), so every sale is in their cash drawer.
 */
class GuichetController extends Controller
{
    /** Shared account of the travellers who gave neither phone nor e-mail (.invalid: never mailed). */
    public const COMPTE_ANONYME = 'voyageur-guichet@blasti.invalid';

    /** Departures listed: from the chosen day, over this many days, at most MAX_DEPARTS. */
    public const JOURS = 7;
    public const MAX_DEPARTS = 40;

    public function index(Request $request)
    {
        $villes = Ville::orderBy('ville')->get(['id', 'ville']);
        $date = $request->date('date')?->toDateString() ?? today()->toDateString();
        $de = $request->integer('de') ?: null;
        $a = $request->integer('a') ?: null;

        // departures from that day on, the next ones first (company accounts only see theirs: global scope)
        $debut = \Carbon\Carbon::parse($date)->startOfDay();
        $departs = ($de ? Voyage::serving($de, $a) : Voyage::query())
            ->whereDate('date_depart', '>=', $debut->copy()->subDay()->toDateString()) // a later stop can be the next day
            ->whereDate('date_depart', '<=', $debut->copy()->addDays(self::JOURS)->toDateString())
            ->with(['villeDepart', 'villeArrivee', 'autocar.societe', 'arrets.ville', 'reservations:id,voyage_id,num_siege,arret_depart_id,arret_arrivee_id'])
            ->get()
            // every part of every trip is sold on its own: Tanger → Tetouan via Oujda also gives
            // Tanger → Oujda and Oujda → Tetouan (same bus, same controller, their own price)
            ->flatMap(fn (Voyage $v) => $this->segments($v, $de, $a))
            // boarding from the chosen day on, closest departure first
            ->filter(fn ($d) => $d->depart->passage_at->gte($debut) && $d->depart->passage_at->isFuture())
            ->sortBy(fn ($d) => [$d->depart->passage_at->getTimestamp(), $d->arrivee->ordre])
            ->take(self::MAX_DEPARTS)->values();

        $choix = $request->filled('voyage') ? $departs->first(fn ($d) => $d->voyage->id === $request->integer('voyage')
            && (! $request->filled('ad') || $d->depart->id === $request->integer('ad'))
            && (! $request->filled('aa') || $d->arrivee->id === $request->integer('aa'))) : null;
        $pris = $choix ? $choix->voyage->seatsTaken($choix->depart, $choix->arrivee) : [];

        return view('admin.guichet.index', compact('villes', 'date', 'de', 'a', 'departs', 'choix', 'pris'));
    }

    /** The sellable parts of a trip: boarding in $de (any stop if null), getting off in $a (any later stop if null). */
    private function segments(Voyage $v, ?int $de, ?int $a)
    {
        $arrets = $v->arrets->values();
        $lignes = collect();
        foreach ($arrets as $i => $montee) {
            if ($de && (int) $montee->ville_id !== $de) {
                continue;
            }
            foreach ($arrets->slice($i + 1) as $descente) {
                if ($a && (int) $descente->ville_id !== $a) {
                    continue;
                }
                $lignes->push((object) [
                    'voyage' => $v, 'depart' => $montee, 'arrivee' => $descente,
                    'complet' => $montee->ordre === $arrets->first()->ordre && $descente->ordre === $arrets->last()->ordre,
                    'prix' => $v->segmentPrice($montee, $descente), 'libres' => $v->seatsLeft($montee, $descente),
                ]);
            }
        }

        return $lignes;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'voyage_id' => ['required', 'integer'],
            'arret_depart_id' => ['required', 'integer'],
            'arret_arrivee_id' => ['required', 'integer'],
            'seats' => ['required', 'array', 'min:1', 'max:' . config('safar.max_sieges')],
            'seats.*' => ['required', 'integer', 'min:1', 'distinct'],
            'passagers' => ['nullable', 'array'],
            'passagers.*' => ['nullable', 'string', 'max:120'],
            'nom' => ['required', 'string', 'max:120'],
            'telephone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9 +().-]{8,}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'mode' => ['required', 'in:' . implode(',', array_keys(Encaissement::MODES))],
        ], [
            'seats.required' => 'Choisissez au moins un siège.',
            'nom.required' => 'Le nom du voyageur est obligatoire.',
            'telephone.regex' => 'Ce numéro de téléphone n\'est pas valide.',
        ]);
        $vendeur = $request->user();
        $seats = collect($data['seats'])->map(fn ($s) => (int) $s)->sort()->values();

        try {
            $billets = DB::transaction(function () use ($data, $seats, $vendeur) {
                // lock the bus: two sellers (or a seller and a website client) cannot sell the same seat
                $voyage = Voyage::with(['autocar', 'arrets'])->lockForUpdate()->findOrFail($data['voyage_id']);
                $segment = $voyage->segmentByIds($data['arret_depart_id'], $data['arret_arrivee_id']);
                if (! $segment) {
                    throw new \DomainException('Ce trajet n\'est pas proposé par ce bus.');
                }
                [$depart, $arrivee] = $segment;
                if ($depart->passage_at->isPast()) {
                    throw new \DomainException('Ce bus est déjà parti.');
                }
                if (! $voyage->autocar || $seats->max() > $voyage->autocar->nbr_siege) {
                    throw new \DomainException('Ce siège n\'existe pas dans ce bus.');
                }
                $pris = $seats->intersect($voyage->seatsTaken($depart, $arrivee));
                if ($pris->isNotEmpty()) {
                    throw new \DomainException('Siège(s) ' . $pris->join(', ') . ' déjà vendu(s) entre-temps : choisissez-en d\'autres.');
                }

                $client = $this->client($data);
                $commande = Reservation::nouvelleCommande();
                $noms = collect($data['passagers'] ?? [])->map(fn ($n) => trim((string) $n))->filter();
                $prix = $voyage->segmentPrice($depart, $arrivee);

                return $seats->map(fn ($seat) => Reservation::create([
                    'commande' => $commande,
                    'num_siege' => $seat,
                    'passager_nom' => $noms->get($seat) ?: $data['nom'], // the name typed at the counter (shared account: not its name)
                    'statut' => Reservation::CONFIRMEE,
                    'user_id' => $client->id,
                    'vendu_par' => $vendeur->id,
                    'mode_reglement_id' => self::modeGuichet()->id,
                    'date_reservation' => now(),
                    'date_depart' => $depart->passage_at->toDateString(),
                    'date_arrivee' => $arrivee->passage_at->toDateString(),
                    'heure_depart' => $depart->passage_at->format('H:i:s'),
                    'heure_arrivee' => $arrivee->passage_at->format('H:i:s'),
                    'ville_depart_id' => $depart->ville_id,
                    'ville_arrivee_id' => $arrivee->ville_id,
                    'autocar_id' => $voyage->autocar_id,
                    'type_voyage_id' => $voyage->type_voyage_id,
                    'prix' => $prix,
                    'frais' => 0,
                    'voyage_id' => $voyage->id,
                    'arret_depart_id' => $depart->id,
                    'arret_arrivee_id' => $arrivee->id,
                    'presence_confirmee_le' => now(), // paid now: no "pay or confirm" e-mail
                ]))->values();
            });
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // paid at once: one line per ticket in the seller's cash drawer, one e-mail with every ticket (not N receipts)
        $billets->each(fn (Reservation $b) => $b->encaisser($vendeur, $data['mode'], false));
        ReservationMail::sendTo($billets->map->fresh(), 'confirmee');

        return redirect()->route('reservation.admin.guichet.vente', $billets->first()->commande)
            ->with('success', 'Vente enregistrée : ' . $billets->count() . ' billet(s), ' . number_format($billets->sum->prix, 2, ',', ' ') . ' DH encaissés.');
    }

    /** Result of a sale: the tickets, print / WhatsApp / e-mail, and "next customer". */
    public function vente(string $commande)
    {
        $billets = $this->billets($commande);
        $actifs = $billets->reject->isCancelled();

        // free seats each ticket can move to (same bus, same segment; its own seat included)
        $libres = [];
        foreach ($actifs as $b) {
            if ($b->voyage && $b->arretDepart && $b->arretArrivee) {
                $pris = $b->voyage->seatsTaken($b->arretDepart, $b->arretArrivee, $b->id);
                $libres[$b->id] = array_values(array_diff(range(1, (int) ($b->voyage->autocar?->nbr_siege ?? 0)), $pris));
            }
        }

        return view('admin.guichet.vente', [
            'billets' => $billets,
            'actifs' => $actifs,
            'commande' => $commande,
            'client' => $billets->first()->user,
            'whatsapp' => \App\Support\WhatsApp::lien($actifs, $billets->first()->user?->telephone),
            'texte' => \App\Support\WhatsApp::texte($actifs),
            'libres' => $libres,
            'corrigeable' => $actifs->filter(fn ($b) => self::peutCorriger($b, request()->user()))->pluck('id')->all(),
            'correctionJusqua' => $actifs->min('created_at')?->copy()->addMinutes((int) config('safar.guichet_correction_minutes')),
        ]);
    }

    /**
     * A seller may correct their own sale for config('safar.guichet_correction_minutes') after it, before departure and boarding.
     * Later (or someone else's sale): only the super admin or a role with the refund right (Service client, Directeur).
     */
    public static function peutCorriger(Reservation $b, User $user): bool
    {
        if ($b->isCancelled() || $b->isBoarded() || $b->departAt()->isPast() || ! $b->vendu_par) {
            return false;
        }
        if ($user->isSuperAdmin() || $user->hasPermission('reservations.rembourser')) {
            return true;
        }

        return $b->vendu_par === $user->id
            && $b->created_at->gt(now()->subMinutes((int) config('safar.guichet_correction_minutes')));
    }

    /** Wrong seat: the ticket moves to a free seat of the same bus (same ticket, same QR code). */
    public function siege(Request $request, Reservation $reservation)
    {
        abort_unless(self::peutCorriger($reservation, $request->user()), 403, 'Le délai de correction de cette vente est passé.');
        $num = (int) $request->validate(['num_siege' => ['required', 'integer', 'min:1']])['num_siege'];
        $avant = $reservation->num_siege;

        try {
            DB::transaction(function () use ($reservation, $num) {
                $voyage = Voyage::with(['autocar', 'arrets'])->lockForUpdate()->findOrFail($reservation->voyage_id);
                $depart = $voyage->arrets->firstWhere('id', $reservation->arret_depart_id);
                $arrivee = $voyage->arrets->firstWhere('id', $reservation->arret_arrivee_id);
                if ($num > (int) $voyage->autocar?->nbr_siege) {
                    throw new \DomainException('Ce siège n\'existe pas dans ce bus.');
                }
                if (in_array($num, $voyage->seatsTaken($depart, $arrivee, $reservation->id), true)) {
                    throw new \DomainException("Le siège {$num} vient d'être vendu : choisissez-en un autre.");
                }
                $reservation->update(['num_siege' => $num, 'siege_actif' => $num]);
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Billet #{$reservation->id} : siège {$avant} → siège {$num}. Réimprimez ou renvoyez le billet.");
    }

    /** Wrong payment mode typed (cash / card): the cash drawer lines of the sale are corrected. */
    public function mode(Request $request, string $commande)
    {
        $mode = $request->validate(['mode' => ['required', 'in:' . implode(',', array_keys(Encaissement::MODES))]])['mode'];
        $billets = $this->billets($commande)->filter(fn ($b) => self::peutCorriger($b, $request->user()));
        abort_if($billets->isEmpty(), 403, 'Le délai de correction de cette vente est passé.');

        foreach ($billets as $b) {
            $b->encaissements()->where('montant', '>', 0)->update(['mode' => $mode]);
            $b->forceFill(['paiement_ref' => $mode === 'carte' ? 'guichet-carte' : 'guichet'])->save();
        }

        return back()->with('success', 'Mode de paiement corrigé : ' . Encaissement::MODES[$mode] . '.');
    }

    /** One ticket too many (or the whole sale, wrong bus / date): cancelled, money given back, seat free again. */
    public function retirer(Request $request, Reservation $reservation)
    {
        abort_unless(self::peutCorriger($reservation, $request->user()), 403, 'Le délai de correction de cette vente est passé.');
        $rendu = $this->rendre($reservation, $request->user());

        return back()->with('success', "Billet #{$reservation->id} (siège {$reservation->num_siege}) retiré : rendez " . number_format($rendu, 2, ',', ' ') . ' DH au voyageur.');
    }

    public function annuler(Request $request, string $commande)
    {
        $billets = $this->billets($commande)->filter(fn ($b) => self::peutCorriger($b, $request->user()));
        abort_if($billets->isEmpty(), 403, 'Le délai de correction de cette vente est passé.');
        $rendu = $billets->sum(fn ($b) => $this->rendre($b, $request->user()));

        return redirect()->route('reservation.admin.guichet')
            ->with('success', "Vente {$commande} annulée : rendez " . number_format($rendu, 2, ',', ' ') . ' DH au voyageur, puis refaites la vente.');
    }

    /**
     * Cancels a counter ticket and gives the money back at once: a negative line in the cash drawer
     * (same mode as the payment), so the seller's drawer stays right. No e-mail: the traveller is at the counter.
     */
    private function rendre(Reservation $b, User $par): float
    {
        return DB::transaction(function () use ($b, $par) {
            $paye = (float) $b->encaissements()->sum('montant');
            $mode = $b->encaissements()->where('montant', '>', 0)->value('mode') ?? 'especes';
            $b->cancel('admin');
            if ($paye > 0) {
                $b->encaissements()->create(['user_id' => $par->id, 'montant' => -$paye, 'mode' => $mode]);
            }
            $b->forceFill(['montant_rembourse' => $paye, 'rembourse_le' => now()])->save();

            return $paye;
        });
    }

    /** Every ticket of the sale in one PDF (one page + QR code per seat). */
    public function pdf(string $commande)
    {
        return \App\Support\BilletPdf::make($this->billets($commande))
            ->stream('billets-' . $commande . '.pdf');
    }

    /** Receipt printer page (80 or 58 mm roll, or any printer): one small ticket per seat, prints by itself. */
    public function imprimer(Request $request, string $commande)
    {
        return view('admin.guichet.imprimer', [
            'billets' => $this->billets($commande)->reject->isCancelled()->values(),
            'largeur' => $request->integer('largeur') === 58 ? 58 : 80,
        ]);
    }

    /** Typo at the counter: fix the passenger name printed on the ticket (same ticket, same QR code). */
    public function passager(Request $request, Reservation $reservation)
    {
        abort_if($reservation->isCancelled() || $reservation->departAt()->isPast(), 403, 'Ce billet ne peut plus être modifié.');
        $data = $request->validate(['passager_nom' => ['required', 'string', 'max:120']], [
            'passager_nom.required' => 'Le nom du passager est obligatoire.',
        ]);

        $avant = $reservation->passager();
        $reservation->update($data);

        return back()->with('success', "Siège {$reservation->num_siege} : « {$avant} » corrigé en « {$reservation->passager_nom} ». Réimprimez ou renvoyez le billet.");
    }

    private function billets(string $commande)
    {
        $billets = Reservation::withoutGlobalScope('active')->where('commande', $commande)
            ->with(['user', 'villeDepart', 'villeArrivee', 'modeReglement', 'autocar.societe', 'encaissements.user', 'voyage.autocar', 'voyage.arrets', 'arretDepart', 'arretArrivee'])
            ->orderBy('num_siege')->get();
        abort_if($billets->isEmpty(), 404);

        return $billets;
    }

    /**
     * The traveller's account: found by e-mail, else by phone; otherwise created. A traveller without e-mail
     * gets a placeholder address (.invalid: never mailed, see ReservationMail::sendTo) and can sign up later.
     */
    private function client(array $data): User
    {
        $telephone = preg_replace('/\s+/', ' ', trim((string) ($data['telephone'] ?? ''))) ?: null;
        $email = $data['email'] ?? null;

        // no phone, no e-mail: every such sale goes on one shared "counter traveller" account (no junk accounts);
        // the passenger's name is printed on each ticket
        if (! $telephone && ! $email) {
            return User::firstOrCreate(['email' => self::COMPTE_ANONYME], [
                'name' => 'Voyageur guichet',
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
            ]);
        }

        $client = ($email ? User::where('email', $email)->first() : null)
            ?? ($telephone ? User::where('isadmin', 0)->where('telephone', $telephone)->first() : null);

        if ($client?->isadmin) {
            throw new \DomainException('Cet e-mail appartient à un compte de l\'équipe : utilisez l\'e-mail du voyageur.');
        }
        if ($client) {
            // keep the phone for next time
            $client->telephone ??= $telephone;
            $client->save();

            return $client;
        }

        $client = User::create([
            'name' => $data['nom'],
            'email' => $email ?: 'guichet-' . Str::lower(Str::random(10)) . '@blasti.invalid',
            'telephone' => $telephone,
            'password' => Hash::make(Str::random(32)),
        ]);
        $client->forceFill(['email_verified_at' => now()])->save();

        return $client;
    }

    /** Payment mode stored on counter tickets: a "pay later" mode (not online, not agency), created if missing. */
    public static function modeGuichet(): ModeReglement
    {
        return ModeReglement::where('en_ligne', false)->where('en_agence', false)->orderBy('id')->first()
            ?? ModeReglement::create(['mode_reglement' => 'Espèces']);
    }
}

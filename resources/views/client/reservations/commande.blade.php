{{-- Every ticket of an order: shown right after booking / paying (ReservationController@order) --}}
@php
    $first = $billets->first();
    $actifs = $billets->reject->isCancelled();
    $enAttente = $actifs->where('statut', \App\Models\Reservation::EN_ATTENTE)->isNotEmpty();
    $aPayer = $actifs->sum(fn ($b) => $b->resteAPayer());
    $dh = fn ($n) => number_format($n, 2, ',', ' ') . ' ' . __('DH');
    $d = $first->departAt();
@endphp
<x-app-layout>
    <section class="section pt-5">
        <div class="container">
            <ol class="bl-steps">
                <li class="is-done"><span><i class="isax isax-tick-circle5"></i></span>{{ __('Sièges') }}</li>
                <li class="is-done"><span><i class="isax isax-tick-circle5"></i></span>{{ __('Paiement') }}</li>
                <li class="is-current"><span>3</span>{{ __('Billets') }}</li>
            </ol>

            {{-- result --}}
            <div class="bl-order-hero {{ $enAttente ? 'is-waiting' : '' }}">
                <span class="bl-order-hero-icon"><i class="isax {{ $enAttente ? 'isax-timer-1' : 'isax-tick-circle' }}"></i></span>
                <div class="flex-fill">
                    <h3 class="mb-1">{{ $enAttente ? __('Paiement en cours de confirmation') : trans_choice('{1} Votre billet est prêt !|[2,*] Vos :count billets sont prêts !', $actifs->count(), ['count' => $actifs->count()]) }}</h3>
                    <p class="mb-0">
                        {{ __($first->villeDepart?->ville) }} → {{ __($first->villeArrivee?->ville) }} · {{ ucfirst($d->translatedFormat('l d F')) }} · {{ $d->format('H:i') }}
                        · {{ __('Commande :ref', ['ref' => $commande]) }}
                    </p>
                    <p class="mb-0 mt-1 fs-14">
                        @if ($aPayer > 0)
                            <i class="isax isax-wallet-money me-1"></i>{{ __('À régler au contrôleur avant de monter : :montant.', ['montant' => $dh($aPayer)]) }}
                        @elseif (! $enAttente)
                            <i class="isax isax-verify me-1"></i>{{ __('Tout est payé : rien à régler dans le bus.') }}
                        @endif
                        <span class="d-block d-md-inline ms-md-2"><i class="isax isax-sms me-1"></i>{{ __('Les billets (PDF) ont aussi été envoyés à :email.', ['email' => $first->user?->email]) }}</span>
                    </p>
                </div>
                @if ($actifs->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ \App\Support\WhatsApp::lien($actifs) }}" target="_blank" rel="noopener" class="btn bl-btn-whatsapp rounded-pill px-4 fw-semibold"><i class="fab fa-whatsapp me-1"></i>{{ __('Envoyer sur WhatsApp') }}</a>
                        <a href="{{ route('client.commande.download', $commande) }}" class="btn btn-light rounded-pill px-4 fw-semibold"><i class="isax isax-document-download me-1"></i>{{ $actifs->count() > 1 ? __('Tous les billets (PDF)') : __('Billet (PDF)') }}</a>
                    </div>
                @endif
            </div>

            {{-- agency payment: the code to give at the payment point, before the deadline (reservations:agence) --}}
            @if ($aPayer > 0 && $first->modeReglement?->en_agence && ! $first->isPaid())
                @php $limite = $first->created_at->copy()->addHours((int) config('safar.agence_delai_heures')); @endphp
                <div class="bl-agency-code mt-3">
                    <div>
                        <div class="fs-13 text-uppercase text-muted">{{ __('Votre code de paiement') }}</div>
                        <div class="bl-agency-code-value">{{ $commande }}</div>
                    </div>
                    <div class="flex-fill fs-14">
                        {{ __('Présentez ce code dans un point de paiement (Wafacash, Cash Plus, agence :brand) et payez :montant avant le :date à :heure. Sans paiement, les billets seront annulés automatiquement.', ['brand' => config('safar.nom'), 'montant' => $dh($aPayer), 'date' => $limite->translatedFormat('d M'), 'heure' => $limite->format('H:i')]) }}
                    </div>
                </div>
            @endif

            {{-- one card per seat --}}
            <div class="row g-3 mt-1">
                @foreach ($billets as $billet)
                    @php [$badge, $tone] = $billet->statusBadge(); $tone = ['muted' => 'secondary'][$tone] ?? $tone; @endphp
                    <div class="col-xl-4 col-md-6">
                        <div class="bl-ticket-card {{ $billet->isCancelled() ? 'is-cancelled' : '' }}">
                            <div class="bl-ticket-card-main">
                                <div>
                                    <div class="fs-12 text-muted text-uppercase">{{ __('Siège') }}</div>
                                    <div class="bl-ticket-seat">{{ $billet->num_siege }}</div>
                                    <div class="fw-semibold">{{ $billet->passager() }}</div>
                                    <div class="fs-13 text-muted">{{ __('Billet N° :id', ['id' => $billet->id]) }}</div>
                                    <span class="badge bg-{{ $tone }}-transparent text-{{ $tone }} mt-2">{{ $badge }}</span>
                                </div>
                                @unless ($billet->isCancelled())
                                    <img src="{{ $billet->qrCodeDataUri(110) }}" width="110" height="110" alt="{{ __('QR code du billet') }}" class="bl-ticket-qr">
                                @endunless
                            </div>
                            <div class="bl-ticket-card-actions">
                                <a href="{{ route('ticket.show', $billet->id) }}"><i class="isax isax-eye me-1"></i>{{ __('Voir') }}</a>
                                @unless ($billet->isCancelled())
                                    <a href="{{ route('ticket.download', $billet->id) }}"><i class="isax isax-document-download me-1"></i>PDF</a>
                                @endunless
                                @if ($billet->canBeChangedByClient())
                                    <a href="{{ route('client.reservations.change', $billet->id) }}"><i class="isax isax-calendar-edit me-1"></i>{{ __('Modifier la date') }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- round trip: book the return right away (discount config safar.remise_retour_pourcent) --}}
            @if ($actifs->isNotEmpty() && ! $first->retour_de && $first->departAt()->isFuture())
                @php $remiseRetour = (float) config('safar.remise_retour_pourcent'); @endphp
                <form method="GET" action="{{ route('voyages.client.index') }}" class="bl-return-card mt-4">
                    <input type="hidden" name="ville_depart" value="{{ $first->ville_arrivee_id }}">
                    <input type="hidden" name="ville_arrivee" value="{{ $first->ville_depart_id }}">
                    <input type="hidden" name="retour_de" value="{{ $commande }}">
                    <span class="bl-return-icon"><i class="isax isax-arrow-2"></i></span>
                    <div class="flex-fill">
                        <h5 class="mb-1">{{ __('Et le retour ?') }}</h5>
                        <p class="mb-0 text-muted fs-14">
                            {{ __($first->villeArrivee?->ville) }} → {{ __($first->villeDepart?->ville) }}
                            @if ($remiseRetour > 0) · <strong class="text-success">{{ __(':p % de réduction sur le billet retour', ['p' => rtrim(rtrim(number_format($remiseRetour, 2, ',', ''), '0'), ',')]) }}</strong>@endif
                        </p>
                    </div>
                    <div class="bl-return-date">
                        <input type="date" name="date_depart" class="form-control" min="{{ \Carbon\Carbon::parse($first->date_arrivee)->toDateString() }}" required
                               data-bl-date data-bl-placeholder="{{ __('Date du retour') }}" data-bl-clear="{{ __('Effacer') }}" data-bl-today="{{ __("Aujourd'hui") }}" data-bl-prev="{{ __('Mois précédent') }}" data-bl-next="{{ __('Mois suivant') }}">
                    </div>
                    <button class="btn btn-primary rounded-pill px-4">{{ __('Voir les départs') }}</button>
                </form>
            @endif

            <div class="text-center mt-4">
                <a href="{{ route('client.profile.reservations.index') }}" class="btn btn-outline-primary rounded-pill px-4">{{ __('Mes réservations') }}</a>
                <a href="{{ route('home') }}" class="btn btn-link">{{ __('Retour à l\'accueil') }}</a>
            </div>
        </div>
    </section>
</x-app-layout>

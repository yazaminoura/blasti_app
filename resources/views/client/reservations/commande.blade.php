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
                    <a href="{{ route('client.commande.download', $commande) }}" class="btn btn-light rounded-pill px-4 fw-semibold"><i class="isax isax-document-download me-1"></i>{{ $actifs->count() > 1 ? __('Tous les billets (PDF)') : __('Billet (PDF)') }}</a>
                @endif
            </div>

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

            <div class="text-center mt-4">
                <a href="{{ route('client.profile.reservations.index') }}" class="btn btn-outline-primary rounded-pill px-4">{{ __('Mes réservations') }}</a>
                <a href="{{ route('home') }}" class="btn btn-link">{{ __('Retour à l\'accueil') }}</a>
            </div>
        </div>
    </section>
</x-app-layout>

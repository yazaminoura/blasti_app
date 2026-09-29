{{-- Opened by scanning the QR code of a ticket (signed link): is this ticket valid for boarding? --}}
@php
    $r = $reservation;
    $parti = $r->departAt()->copy()->addHours(2)->isPast();
    $aPayer = $r->resteAPayer();
    [$etat, $tone, $icon] = match (true) {
        $r->isCancelled() => [__('Billet annulé : non valable'), 'danger', 'isax-close-circle'],
        $r->statut === \App\Models\Reservation::EN_ATTENTE => [__('Paiement non confirmé : non valable'), 'warning', 'isax-timer-1'],
        $parti => [__('Voyage déjà effectué'), 'secondary', 'isax-clock'],
        // valid seat, but the money is not in yet: the controller collects it before boarding
        $aPayer > 0 => [__('Billet NON PAYÉ'), 'warning', 'isax-wallet-remove'],
        default => [__('Billet valable · payé'), 'success', 'isax-tick-circle'],
    };
@endphp
<x-app-layout>
    <div class="content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <span class="avatar avatar-xl rounded-circle bg-{{ $tone }} text-white mb-3"><i class="isax {{ $icon }} fs-32"></i></span>
                            <h3 class="mb-1 text-{{ $tone }}">{{ $etat }}</h3>
                            <p class="text-muted mb-3">{{ __('Billet N° :id', ['id' => $r->id]) }}@if ($r->commande) · {{ $r->commande }}@endif</p>

                            @if (! $r->isCancelled() && ! $parti && $aPayer > 0)
                                <div class="alert alert-warning text-start d-flex gap-3 align-items-center mb-4">
                                    <i class="isax isax-money-send fs-24"></i>
                                    <div>
                                        <div class="fw-bold fs-18">{{ __('À encaisser avant la montée : :montant DH', ['montant' => number_format($aPayer, 2, ',', ' ')]) }}</div>
                                        <div class="fs-14">{{ $r->isPaid() ? __('Supplément après changement de départ.') : __('Le voyageur n\'a pas encore payé ce billet.') }}</div>
                                    </div>
                                </div>
                            @endif

                            {{-- controller at the bus door (back-office account with the "réservations" permission) --}}
                            @if (auth()->user()?->isadmin && auth()->user()->hasPermission('reservations.update') && ! $r->isCancelled() && $r->statut !== \App\Models\Reservation::EN_ATTENTE)
                                <div class="bl-staff-panel mb-4">
                                    <div class="fs-12 text-uppercase text-muted mb-2"><i class="isax isax-security-user me-1"></i>{{ __('Contrôleur') }}</div>
                                    @if ($r->isBoarded())
                                        <div class="alert alert-success mb-0"><i class="isax isax-tick-circle me-1"></i>{{ __('Embarqué à :heure', ['heure' => $r->embarque_le->format('H:i')]) }}</div>
                                    @else
                                        <div class="d-grid gap-2">
                                            @if ($aPayer > 0)
                                                <form method="POST" action="{{ route('reservation.admin.payer', $r) }}">
                                                    @csrf @method('PATCH')
                                                    <button class="btn btn-warning btn-lg w-100"><i class="isax isax-money-recive me-1"></i>{{ __('Encaisser :montant DH', ['montant' => number_format($aPayer, 2, ',', ' ')]) }}</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('reservation.admin.embarquer', $r) }}">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-success btn-lg w-100" @disabled($aPayer > 0)><i class="isax isax-login me-1"></i>{{ __('Faire monter le voyageur') }}</button>
                                            </form>
                                        </div>
                                    @endif
                                    <a href="{{ route('voyages.passagers', $r->voyage_id) }}" class="d-block fs-14 mt-2"><i class="isax isax-people me-1"></i>{{ __('Liste des passagers de ce bus') }}</a>
                                </div>
                            @endif

                            <ul class="list-group list-group-flush text-start">
                                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('Passager') }}</span><strong>{{ $r->user?->name }}</strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('Trajet') }}</span><strong>{{ __($r->villeDepart?->ville) }} → {{ __($r->villeArrivee?->ville) }}</strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('Départ') }}</span><strong>{{ __(':date à :heure', ['date' => $r->departAt()->format('d/m/Y'), 'heure' => $r->departAt()->format('H:i')]) }}</strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('Siège') }}</span><strong class="fs-18">{{ $r->num_siege }}</strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('Autocar') }}</span><strong>{{ $r->autocar?->societe?->raison_social ?? '—' }} · {{ $r->autocar?->matricule ?? '—' }}</strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('Paiement') }}</span><strong>{{ $r->statusBadge()[0] }} · {{ number_format($r->total(), 2, ',', ' ') }} {{ __('DH') }}</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

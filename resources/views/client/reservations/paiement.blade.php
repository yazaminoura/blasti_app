{{-- Step 2 of the booking: order summary + payment choice (ReservationController@payment → store) --}}
@php
    $dh = fn ($n) => number_format($n, 2, ',', ' ') . ' ' . __('DH');
    $total = $prix * count($seats);
    $minutes = $depart->passage_at->diffInMinutes($arrivee->passage_at);
    $test = \App\Support\Cmi::testMode();
    $default = old('mode_reglement_id', optional($modes->firstWhere('en_ligne', true) ?? $modes->first())->id);
@endphp
<x-app-layout>
    @include('partials.page-banner', ['title' => __('Paiement'), 'subtitle' => __($depart->ville->ville) . ' → ' . __($arrivee->ville->ville)])

    <section class="section pt-4">
        <div class="container">
            {{-- steps --}}
            <ol class="bl-steps">
                <li class="is-done"><span><i class="isax isax-tick-circle5"></i></span>{{ __('Sièges') }}</li>
                <li class="is-current"><span>2</span>{{ __('Paiement') }}</li>
                <li><span>3</span>{{ __('Billets') }}</li>
            </ol>

            <form method="POST" action="{{ route('client.reservations.store') }}" id="pay-form">
                @csrf
                <input type="hidden" name="voyage_id" value="{{ $voyage->id }}">
                <input type="hidden" name="arret_depart_id" value="{{ $depart->id }}">
                <input type="hidden" name="arret_arrivee_id" value="{{ $arrivee->id }}">
                @foreach ($seats as $seat)
                    <input type="hidden" name="seats[]" value="{{ $seat }}">
                @endforeach

                <div class="row g-4">
                    <div class="col-lg-7">
                        <h5 class="mb-3">{{ __('Comment voulez-vous payer ?') }}</h5>

                        @if ($errors->any())
                            <div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                        @endif

                        <div class="bl-paymodes">
                            @foreach ($modes as $mode)
                                <label class="bl-paymode">
                                    <input type="radio" name="mode_reglement_id" value="{{ $mode->id }}" @checked($default == $mode->id) required>
                                    <span class="bl-paymode-icon"><i class="isax {{ $mode->en_ligne ? 'isax-card' : 'isax-bus' }}"></i></span>
                                    <span class="bl-paymode-body">
                                        <span class="bl-paymode-title">
                                            {{ $mode->en_ligne ? __('Payer maintenant par carte') : __('Payer à l\'embarquement') }}
                                            <span class="text-muted fw-normal">· {{ __($mode->mode_reglement) }}</span>
                                            @if ($mode->en_ligne && $test)<span class="badge bg-warning-transparent text-warning ms-1">{{ __('MODE TEST') }}</span>@endif
                                        </span>
                                        <span class="bl-paymode-text">
                                            {{ $mode->en_ligne
                                                ? __('Paiement sécurisé CMI (Visa, Mastercard, cartes marocaines). Vos billets sont payés tout de suite : rien à régler dans le bus.')
                                                : __('Vos sièges sont réservés maintenant ; vous payez :total au contrôleur avant de monter.', ['total' => $dh($total)]) . (config('safar.confirmation.active') ? ' ' . __('Nous vous demanderons par e-mail de confirmer votre présence :h h avant le départ.', ['h' => config('safar.confirmation.demande_heures')]) : '') }}
                                        </span>
                                    </span>
                                    <span class="bl-paymode-check"><i class="isax isax-tick-circle5"></i></span>
                                </label>
                            @endforeach
                        </div>

                        @if ($cashRefused)
                            <div class="alert alert-warning fs-14 mt-3"><i class="isax isax-info-circle me-1"></i>{{ __('Le paiement à l\x27embarquement n\x27est plus proposé sur votre compte après plusieurs billets non utilisés : merci de payer par carte.') }}</div>
                        @endif

                        @if ($modes->where('en_ligne', true)->isEmpty())
                            <p class="text-muted fs-13 mt-2"><i class="isax isax-info-circle me-1"></i>{{ __('Le paiement en ligne par carte sera bientôt disponible.') }}</p>
                        @endif

                        <div class="mt-4">
                            @include('partials.refund-policy', ['compact' => true])
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm bl-order-summary">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase fs-12 mb-3">{{ __('Votre commande') }}</h6>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <div class="fw-bold fs-18">{{ __($depart->ville->ville) }}</div>
                                        <div class="text-primary fw-semibold">{{ $depart->passage_at->format('H:i') }}</div>
                                    </div>
                                    <div class="text-center text-muted fs-12 px-2 flex-fill"><i class="isax isax-bus d-block fs-20 text-primary"></i>{{ intdiv($minutes, 60) }} h {{ str_pad($minutes % 60, 2, '0', STR_PAD_LEFT) }}</div>
                                    <div class="text-end">
                                        <div class="fw-bold fs-18">{{ __($arrivee->ville->ville) }}</div>
                                        <div class="text-primary fw-semibold">{{ $arrivee->passage_at->format('H:i') }}</div>
                                    </div>
                                </div>
                                <div class="fs-14 text-muted mb-3">
                                    <i class="isax isax-calendar-1 me-1"></i>{{ ucfirst($depart->passage_at->translatedFormat('l d F Y')) }}<br>
                                    <i class="isax isax-building me-1"></i>{{ $voyage->autocar?->societe?->raison_social }} · {{ $voyage->autocar?->matricule }}
                                </div>
                                <div class="mb-3">
                                    <div class="fs-13 text-muted mb-1">{{ trans_choice('{1} Votre siège|[2,*] Vos :count sièges', count($seats), ['count' => count($seats)]) }}</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($seats as $seat)
                                            <span class="bl-seat-chip">{{ $seat }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="border-top pt-3">
                                    <div class="d-flex justify-content-between fs-14 mb-1"><span class="text-muted">{{ count($seats) }} × {{ $dh($prix) }}</span><span>{{ $dh($total) }}</span></div>
                                    <div class="d-flex justify-content-between align-items-center"><strong>{{ __('Total') }}</strong><strong class="fs-22 text-primary">{{ $dh($total) }}</strong></div>
                                </div>

                                <button type="submit" class="btn-confirm mt-3" id="pay-button" data-card="{{ __('Payer :total', ['total' => $dh($total)]) }}" data-cash="{{ __('Confirmer la réservation') }}">
                                    {{ __('Confirmer la réservation') }}
                                </button>
                                <a href="{{ route('client.reservations.show', ['voyage' => $voyage, 'de' => $depart->id, 'a' => $arrivee->id]) }}" class="d-block text-center fs-14 mt-2"><i class="isax isax-arrow-left-2 me-1"></i>{{ __('Modifier mes sièges') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <script>
        // button label follows the chosen payment ("Payer 300,00 DH" / "Confirmer la réservation")
        (function () {
            const form = document.getElementById('pay-form');
            const button = document.getElementById('pay-button');
            const online = @json($modes->where('en_ligne', true)->pluck('id')->values());
            const sync = () => {
                const checked = form.querySelector('input[name="mode_reglement_id"]:checked');
                button.innerHTML = '<i class="isax ' + (checked && online.includes(Number(checked.value)) ? 'isax-card' : 'isax-tick-circle') + ' me-1"></i>'
                    + (checked && online.includes(Number(checked.value)) ? button.dataset.card : button.dataset.cash);
            };
            form.addEventListener('change', sync);
            form.addEventListener('submit', () => { button.disabled = true; });
            sync();
        })();
    </script>
</x-app-layout>

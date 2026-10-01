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
                <input type="hidden" name="prix_affiche" value="{{ $prix }}">
                <input type="hidden" name="arret_depart_id" value="{{ $depart->id }}">
                <input type="hidden" name="arret_arrivee_id" value="{{ $arrivee->id }}">
                @foreach ($seats as $seat)
                    <input type="hidden" name="seats[]" value="{{ $seat }}">
                @endforeach

                <div class="row g-4">
                    <div class="col-lg-7">
                        {{-- one name per seat: printed on each ticket, checked by the controller --}}
                        <h5 class="mb-1">{{ trans_choice('{1} Qui voyage ?|[2,*] Qui voyage ? (:count voyageurs)', count($seats), ['count' => count($seats)]) }}</h5>
                        <p class="text-muted fs-14 mb-3">{{ __('Le nom est imprimé sur le billet : le contrôleur peut demander une pièce d\'identité.') }}</p>
                        <div class="bl-passengers mb-4">
                            @foreach ($seats as $seat)
                                <label class="bl-passenger">
                                    <span class="bl-seat-chip">{{ $seat }}</span>
                                    <input type="text" name="passagers[{{ $seat }}]" class="form-control" maxlength="120" required
                                           value="{{ old('passagers.' . $seat, $loop->first ? auth()->user()->name : '') }}"
                                           placeholder="{{ __('Nom et prénom du voyageur') }}" autocomplete="{{ $loop->first ? 'name' : 'off' }}">
                                </label>
                            @endforeach
                        </div>

                        <h5 class="mb-3">{{ __('Comment voulez-vous payer ?') }}</h5>

                        @if ($errors->any())
                            <div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                        @endif

                        <div class="bl-paymodes">
                            @foreach ($modes as $mode)
                                <label class="bl-paymode">
                                    <input type="radio" name="mode_reglement_id" value="{{ $mode->id }}" @checked($default == $mode->id) required>
                                    <span class="bl-paymode-icon"><i class="isax {{ $mode->en_ligne ? 'isax-card' : ($mode->en_agence ? 'isax-shop' : 'isax-bus') }}"></i></span>
                                    <span class="bl-paymode-body">
                                        <span class="bl-paymode-title">
                                            {{ $mode->en_ligne ? __('Payer maintenant par carte') : ($mode->en_agence ? __('Payer en agence') : __('Payer à l\'embarquement')) }}
                                            <span class="text-muted fw-normal">· {{ __($mode->mode_reglement) }}</span>
                                            @if ($mode->en_ligne && $test)<span class="badge bg-warning-transparent text-warning ms-1">{{ __('MODE TEST') }}</span>@endif
                                        </span>
                                        <span class="bl-paymode-text">
                                            @if ($mode->en_ligne)
                                                {{ __('Paiement sécurisé CMI (Visa, Mastercard, cartes marocaines). Vos billets sont payés tout de suite : rien à régler dans le bus.') }}
                                            @elseif ($mode->en_agence)
                                                {{ __('Vous recevez un code de paiement : payez :total en espèces dans un point de paiement (Wafacash, Cash Plus...) dans les :h heures. Sans paiement, les billets sont annulés.', ['total' => $dh($total), 'h' => config('safar.agence_delai_heures')]) }}
                                            @else
                                                {{ __('Vos sièges sont réservés maintenant ; vous payez :total au contrôleur avant de monter.', ['total' => $dh($total)]) . (config('safar.confirmation.active') ? ' ' . __('Nous vous enverrons un e-mail :h h après la réservation : payez ou confirmez votre présence, sinon les billets sont annulés. Si le bus part avant, vous payez au contrôleur.', ['h' => config('safar.confirmation.demande_heures')]) : '') }}
                                            @endif
                                        </span>
                                    </span>
                                    <span class="bl-paymode-check"><i class="isax isax-tick-circle5"></i></span>
                                </label>
                            @endforeach
                        </div>

                        @if ($quotaAtteint)
                            <div class="alert alert-warning fs-14 mt-3"><i class="isax isax-info-circle me-1"></i>{{ __('Vous avez déjà réservé :max fois sans payer ce mois-ci : merci de payer par carte. Le paiement plus tard revient le mois prochain.', ['max' => config('safar.non_payes_par_mois')]) }}</div>
                        @elseif ($cashRefused)
                            <div class="alert alert-warning fs-14 mt-3"><i class="isax isax-info-circle me-1"></i>{{ __('Le paiement à l\'embarquement n\'est plus proposé sur votre compte après plusieurs billets non utilisés : merci de payer par carte.') }}</div>
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
                                {{-- promo code: checked live (client.reservations.promo), applied again when booking --}}
                                <div class="bl-promo mb-3">
                                    <label class="fs-13 text-muted mb-1" for="code_promo">{{ __('Code promo') }}</label>
                                    <div class="d-flex gap-2">
                                        <input type="text" name="code_promo" id="code_promo" class="form-control text-uppercase" maxlength="30" value="{{ old('code_promo') }}" placeholder="{{ __('Ex : ETE2026') }}" autocomplete="off">
                                        <button type="button" class="btn btn-light" id="promo-apply">{{ __('Appliquer') }}</button>
                                    </div>
                                    <div class="fs-13 mt-1" id="promo-message"></div>
                                </div>
                                @if ($retourDe)
                                    <input type="hidden" name="retour_de" value="{{ $retourDe }}">
                                @endif

                                <div class="border-top pt-3">
                                    <div class="d-flex justify-content-between fs-14 mb-1"><span class="text-muted">{{ count($seats) }} × {{ $dh($prix) }}</span><span>{{ $dh($total) }}</span></div>
                                    @if ($remiseRetour > 0)
                                        <div class="d-flex justify-content-between fs-14 mb-1 text-success" id="retour-line"><span><i class="isax isax-arrow-2 me-1"></i>{{ __('Réduction aller-retour (-:p %)', ['p' => rtrim(rtrim(number_format((float) config('safar.remise_retour_pourcent'), 2, ',', ''), '0'), ',')]) }}</span><span>-{{ $dh($remiseRetour) }}</span></div>
                                    @endif
                                    <div class="d-flex justify-content-between fs-14 mb-1 text-success d-none" id="promo-line"><span><i class="isax isax-ticket-discount me-1"></i><span id="promo-label"></span></span><span id="promo-amount"></span></div>
                                    <div class="d-flex justify-content-between align-items-center"><strong>{{ __('Total') }}</strong><strong class="fs-22 text-primary" id="order-total">{{ $dh($total - $remiseRetour) }}</strong></div>
                                </div>

                                <button type="submit" class="btn-confirm mt-3" id="pay-button" data-card="{{ __('Payer :total', ['total' => '__TOTAL__']) }}" data-cash="{{ __('Confirmer la réservation') }}" data-total="{{ $dh($total - $remiseRetour) }}">
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
                const card = checked && online.includes(Number(checked.value));
                button.innerHTML = '<i class="isax ' + (card ? 'isax-card' : 'isax-tick-circle') + ' me-1"></i>'
                    + (card ? button.dataset.card.replace('__TOTAL__', button.dataset.total) : button.dataset.cash);
            };
            form.addEventListener('change', sync);
            form.addEventListener('submit', () => { button.disabled = true; });
            sync();

            // promo code: live check, the discount and the new total appear in the summary
            const fmt = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const DH = @json(__('DH'));
            const baseTotal = document.getElementById('order-total').textContent;
            const input = document.getElementById('code_promo');
            const msg = document.getElementById('promo-message');
            const apply = async () => {
                const code = input.value.trim();
                const line = document.getElementById('promo-line');
                if (!code) { line.classList.add('d-none'); msg.textContent = ''; document.getElementById('order-total').textContent = baseTotal; button.dataset.total = baseTotal; sync(); return; }
                const res = await fetch(@json(route('client.reservations.promo')), {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ code: code }),
                }).then(r => r.json()).catch(() => ({ ok: false, message: @json(__('Vérification impossible pour le moment.')) }));
                msg.textContent = res.message || '';
                msg.className = 'fs-13 mt-1 ' + (res.ok ? 'text-success' : 'text-danger');
                if (res.ok) {
                    line.classList.remove('d-none');
                    document.getElementById('promo-label').textContent = code.toUpperCase() + ' (' + res.libelle + ')';
                    document.getElementById('promo-amount').textContent = '-' + fmt.format(res.remise) + ' ' + DH;
                    const total = fmt.format(res.total) + ' ' + DH;
                    document.getElementById('order-total').textContent = total;
                    button.dataset.total = total;
                } else {
                    line.classList.add('d-none');
                    document.getElementById('order-total').textContent = baseTotal;
                    button.dataset.total = baseTotal;
                }
                sync();
            };
            document.getElementById('promo-apply').addEventListener('click', apply);
            input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); apply(); } });
            if (input.value) apply();
        })();
    </script>
</x-app-layout>

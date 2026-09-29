{{-- Move a ticket to another departure / seat of the same trip (TicketChangeController) --}}
@php
    $r = $reservation;
    $dh = fn ($n) => number_format($n, 2, ',', ' ') . ' ' . __('DH');
    // price difference shown on each departure, compared with what the ticket costs today
    $ecart = function ($prix) use ($r, $dh) {
        $diff = round($prix - (float) $r->prix, 2);
        if (abs($diff) < 0.01) {
            return [__('Même prix'), 'success'];
        }
        if ($diff > 0) {
            return [$r->isPaid() ? __('+:montant à régler', ['montant' => $dh($diff)]) : __('+:montant', ['montant' => $dh($diff)]), 'warning'];
        }

        return [$r->isPaid() ? __(':montant moins cher (non remboursé)', ['montant' => $dh(-$diff)]) : __(':montant moins cher', ['montant' => $dh(-$diff)]), 'info'];
    };
@endphp
<x-app-layout>
    @include('partials.page-banner', [
        'title' => __('Modifier mon billet'),
        'subtitle' => __($r->villeDepart?->ville) . ' → ' . __($r->villeArrivee?->ville),
    ])

    <section class="section pt-4">
        <div class="container">
            <div class="row g-4">
                {{-- current ticket + rules --}}
                <div class="col-lg-4 order-lg-2">
                    <div class="card border-0 shadow-sm bl-change-current">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase fs-12 mb-3">{{ __('Votre billet actuel') }}</h6>
                            <div class="d-flex justify-content-between mb-2"><span class="text-muted">{{ __('Billet') }}</span><strong>#{{ $r->id }}</strong></div>
                            <div class="d-flex justify-content-between mb-2"><span class="text-muted">{{ __('Départ') }}</span><strong>{{ $r->departAt()->translatedFormat('D d M') }} · {{ $r->departAt()->format('H:i') }}</strong></div>
                            <div class="d-flex justify-content-between mb-2"><span class="text-muted">{{ __('Siège') }}</span><strong>{{ $r->num_siege }}</strong></div>
                            <div class="d-flex justify-content-between mb-3"><span class="text-muted">{{ __('Prix') }}</span><strong>{{ $dh($r->total()) }} · <span class="fw-normal">{{ $r->isPaid() ? __('payé') : __('à régler à l\'embarquement') }}</span></strong></div>
                            <ul class="bl-change-rules">
                                <li><i class="isax isax-clock"></i>{{ __('Modification gratuite jusqu\'au :date à :heure (:h h avant le départ).', ['date' => $r->changeDeadline()->translatedFormat('d M'), 'heure' => $r->changeDeadline()->format('H:i'), 'h' => config('safar.modification_heures')]) }}</li>
                                <li><i class="isax isax-routing-2"></i>{{ __('Même trajet, autre date ou autre heure, et vous choisissez votre nouveau siège.') }}</li>
                                @if ($r->isPaid())
                                    <li><i class="isax isax-money-4"></i>{{ __('Départ plus cher : la différence se règle à l\'embarquement. Départ moins cher : la différence n\'est pas remboursée.') }}</li>
                                @else
                                    <li><i class="isax isax-money-4"></i>{{ __('Vous paierez le prix du nouveau départ à l\'embarquement.') }}</li>
                                @endif
                                <li><i class="isax isax-scan-barcode"></i>{{ __('Votre billet garde son numéro et son QR code ; le nouveau PDF vous est envoyé par e-mail.') }}</li>
                            </ul>
                            <a href="{{ route('ticket.show', $r->id) }}" class="btn btn-light w-100"><i class="isax isax-arrow-left-2 me-1"></i>{{ __('Retour au billet') }}</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 order-lg-1">
                    @if (! $choix)
                        {{-- step 1: pick a departure --}}
                        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
                            <div>
                                <span class="bl-step">1</span>
                                <h4 class="d-inline align-middle mb-0">{{ __('Choisissez le nouveau départ') }}</h4>
                            </div>
                            <form method="GET" class="banner-form bl-change-date">
                                <div class="form-item">
                                    <input type="date" name="date" id="change-date" class="form-control" value="{{ $date?->toDateString() }}" min="{{ now()->toDateString() }}"
                                           onchange="this.form.submit()"
                                           data-bl-date data-bl-placeholder="{{ __('Toutes les dates') }}" data-bl-clear="{{ __('Effacer') }}" data-bl-today="{{ __("Aujourd'hui") }}" data-bl-prev="{{ __('Mois précédent') }}" data-bl-next="{{ __('Mois suivant') }}">
                                </div>
                            </form>
                        </div>

                        @forelse ($departs as $d)
                            @php [$ecartTxt, $ecartTone] = $ecart($d->prix); @endphp
                            <div class="bl-depart {{ $d->actuel ? 'is-current' : '' }}">
                                <div class="bl-depart-date">
                                    <span class="bl-depart-day">{{ $d->depart->passage_at->translatedFormat('D') }}</span>
                                    <strong>{{ $d->depart->passage_at->format('d') }}</strong>
                                    <span>{{ $d->depart->passage_at->translatedFormat('M') }}</span>
                                </div>
                                <div class="bl-depart-times">
                                    <strong>{{ $d->depart->passage_at->format('H:i') }}</strong>
                                    <span class="bl-depart-line">{{ intdiv($d->depart->passage_at->diffInMinutes($d->arrivee->passage_at), 60) }} h {{ str_pad($d->depart->passage_at->diffInMinutes($d->arrivee->passage_at) % 60, 2, '0', STR_PAD_LEFT) }}</span>
                                    <strong>{{ $d->arrivee->passage_at->format('H:i') }}</strong>
                                </div>
                                <div class="bl-depart-info">
                                    <div class="fw-semibold">{{ $d->voyage->autocar?->societe?->raison_social }}</div>
                                    <div class="fs-13 text-muted">{{ trans_choice('{0} complet|{1} :count place libre|[2,*] :count places libres', $d->libres, ['count' => $d->libres]) }}</div>
                                </div>
                                <div class="bl-depart-price">
                                    <strong>{{ $dh($d->prix) }}</strong>
                                    <span class="badge bg-{{ $ecartTone }}-transparent text-{{ $ecartTone }}">{{ $ecartTxt }}</span>
                                </div>
                                <div class="bl-depart-go">
                                    @if ($d->libres > 0)
                                        <a href="{{ route('client.reservations.change', ['reservation' => $r->id, 'voyage' => $d->voyage->id, 'date' => $date?->toDateString()]) }}" class="btn btn-primary btn-sm rounded-pill px-3">
                                            {{ $d->actuel ? __('Changer de siège') : __('Choisir') }}
                                        </a>
                                    @else
                                        <span class="badge bg-light text-muted">{{ __('Complet') }}</span>
                                    @endif
                                </div>
                                @if ($d->actuel)<span class="bl-depart-flag">{{ __('Votre départ actuel') }}</span>@endif
                            </div>
                        @empty
                            <div class="text-center border rounded-4 p-5 text-muted">
                                <i class="isax isax-calendar-remove fs-32 d-block mb-2"></i>
                                {{ $date ? __('Aucun départ ce jour-là pour ce trajet. Essayez une autre date.') : __('Aucun autre départ à venir pour ce trajet pour le moment.') }}
                            </div>
                        @endforelse
                    @else
                        {{-- step 2: pick the seat on the chosen departure --}}
                        @php [$ecartTxt, $ecartTone] = $ecart($choix->prix); @endphp
                        <div class="mb-3">
                            <a href="{{ route('client.reservations.change', ['reservation' => $r->id, 'date' => $date?->toDateString()]) }}" class="fs-14"><i class="isax isax-arrow-left-2 me-1"></i>{{ __('Choisir un autre départ') }}</a>
                        </div>
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div>
                                    <span class="bl-step">2</span>
                                    <h5 class="d-inline align-middle mb-0">{{ __('Choisissez votre siège') }}</h5>
                                    <div class="text-muted mt-1">{{ $choix->depart->passage_at->translatedFormat('l d F') }} · {{ $choix->depart->passage_at->format('H:i') }} → {{ $choix->arrivee->passage_at->format('H:i') }} · {{ $choix->voyage->autocar?->societe?->raison_social }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold fs-18 text-primary">{{ $dh($choix->prix) }}</div>
                                    <span class="badge bg-{{ $ecartTone }}-transparent text-{{ $ecartTone }}">{{ $ecartTxt }}</span>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('client.reservations.change.update', $r->id) }}" id="change-form">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="voyage_id" value="{{ $choix->voyage->id }}">
                        </form>

                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                @include('client.reservations._seat-map', [
                                    'totalSeats' => $choix->voyage->autocar?->nbr_siege ?? 0,
                                    'reservedSeats' => $reservedSeats,
                                    'form' => 'change-form',
                                    'selected' => $choix->actuel ? [$r->num_siege] : [],
                                    'max' => 1,
                                ])
                            </div>
                        </div>

                        <button type="submit" form="change-form" class="btn-confirm" id="change-confirm"
                                data-confirm="{{ __('Déplacer votre billet sur ce départ ? L\'ancien billet ne sera plus valable.') }}">
                            <i class="isax isax-refresh me-1"></i>{{ __('Confirmer la modification') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($choix)
        <script>
            // one seat only: picking a seat releases the previous choice
            (function () {
                const inputs = Array.from(document.querySelectorAll('.seat-input'));
                const refresh = () => inputs.forEach(i => i.closest('.bl-seat').classList.toggle('is-selected', i.checked));
                inputs.forEach(i => i.addEventListener('change', () => {
                    if (i.checked) inputs.forEach(o => { if (o !== i) o.checked = false; });
                    refresh();
                }));
                refresh();
                document.getElementById('change-form').addEventListener('submit', function (e) {
                    if (!inputs.some(i => i.checked)) {
                        e.preventDefault();
                        alert(@json(__('Veuillez choisir un siège.')));
                        return;
                    }
                    if (!confirm(document.getElementById('change-confirm').dataset.confirm)) e.preventDefault();
                });
            })();
        </script>
    @endif
</x-app-layout>

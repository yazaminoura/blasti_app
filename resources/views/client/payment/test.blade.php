{{-- Stand-in for the CMI card page on a developer PC without CMI keys (Cmi::testMode). No money moves. --}}
<x-app-layout>
    <section class="section pt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-8">
                    <div class="alert alert-warning d-flex gap-2 align-items-start">
                        <i class="isax isax-warning-2 fs-20"></i>
                        <div><strong>{{ __('Mode test') }}</strong> · {{ __('Page de paiement factice, visible seulement sur votre ordinateur tant que les clés CMI ne sont pas configurées. Aucun montant n\'est débité.') }}</div>
                    </div>

                    <div class="card border-0 shadow-sm bl-test-pay">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0"><i class="isax isax-card me-1 text-primary"></i>{{ __('Paiement par carte') }}</h5>
                                <span class="badge bg-light text-muted">CMI · TEST</span>
                            </div>
                            <div class="d-flex justify-content-between border rounded-3 p-3 mb-3">
                                <div>
                                    <div class="fs-13 text-muted">{{ __($reservation->villeDepart?->ville) }} → {{ __($reservation->villeArrivee?->ville) }}</div>
                                    <div class="fs-14">{{ trans_choice('{1} Siège :seats|[2,*] Sièges :seats', $billets->count(), ['seats' => $billets->pluck('num_siege')->join(', ')]) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fs-13 text-muted">{{ __('Montant') }}</div>
                                    <div class="fw-bold fs-20 text-primary">{{ number_format((float) $montant, 2, ',', ' ') }} {{ __('DH') }}</div>
                                </div>
                            </div>

                            {{-- display only: nothing typed here is sent anywhere --}}
                            <div class="mb-2">
                                <label class="form-label fs-13">{{ __('Numéro de carte') }}</label>
                                <input class="form-control" value="4000 0000 0000 0002" disabled>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6"><label class="form-label fs-13">{{ __('Expiration') }}</label><input class="form-control" value="12/30" disabled></div>
                                <div class="col-6"><label class="form-label fs-13">CVV</label><input class="form-control" value="123" disabled></div>
                            </div>

                            <form method="POST" action="{{ route('payment.test', $reservation) }}" class="d-grid gap-2">
                                @csrf
                                <button type="submit" name="resultat" value="ok" class="btn-confirm"><i class="isax isax-lock me-1"></i>{{ __('Payer :montant DH', ['montant' => number_format((float) $montant, 2, ',', ' ')]) }}</button>
                                <button type="submit" name="resultat" value="refus" class="btn btn-light">{{ __('Simuler un paiement refusé') }}</button>
                            </form>
                            <p class="text-muted fs-12 mt-3 mb-0">{{ __('Vos sièges sont bloqués :min minutes pendant le paiement.', ['min' => \App\Models\Reservation::DELAI_PAIEMENT_MINUTES]) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

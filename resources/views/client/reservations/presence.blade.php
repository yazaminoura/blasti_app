{{-- Answer page of the "Je confirme ma présence" e-mail button --}}
@php
    $r = $reservation;
    [$titre, $texte, $tone, $icon] = match ($etat) {
        'annule' => [__('Ce billet est annulé'), __('Il n\'est plus possible de le confirmer. Vous pouvez réserver à nouveau si des places sont libres.'), 'danger', 'isax-close-circle'],
        'parti' => [__('Ce voyage est déjà parti'), __('Il n\'y a plus rien à confirmer pour ce billet.'), 'secondary', 'isax-clock'],
        default => [__('Merci, votre siège est gardé !'), __('Votre présence est confirmée. Payez :montant avant le :date à :heure, sinon le billet sera annulé. Si votre bus part avant, payez au contrôleur avant de monter.', ['montant' => number_format($r->resteAPayer(), 2, ',', ' ') . ' ' . __('DH'), 'date' => $r->paymentDeadline()->translatedFormat('d M'), 'heure' => $r->paymentDeadline()->format('H:i')]), 'success', 'isax-tick-circle'],
    };
@endphp
<x-app-layout>
    <section class="section pt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body p-5">
                            <span class="avatar avatar-xl rounded-circle bg-{{ $tone }} text-white mb-3"><i class="isax {{ $icon }} fs-32"></i></span>
                            <h3 class="mb-2">{{ $titre }}</h3>
                            <p class="text-muted mb-4">{{ $texte }}</p>
                            <div class="border rounded-3 p-3 text-start mb-4">
                                <div class="fw-semibold">{{ __($r->villeDepart?->ville) }} → {{ __($r->villeArrivee?->ville) }}</div>
                                <div class="text-muted fs-14">{{ ucfirst($r->departAt()->translatedFormat('l d F')) }} · {{ $r->departAt()->format('H:i') }} · {{ __('Siège :num', ['num' => $r->num_siege]) }}</div>
                            </div>
                            <a href="{{ route('home') }}" class="btn btn-primary rounded-pill px-4">{{ __('Retour à l\'accueil') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

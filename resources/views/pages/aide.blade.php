<x-app-layout>
    @include('partials.page-banner', [
        'title' => __('Centre d\'aide'),
        'subtitle' => __('Réserver, payer, annuler : tout ce qu\'il faut savoir avant de partir.'),
    ])

    <section class="section">
        <div class="container">
            {{-- Guides --}}
            <div class="row g-4 mb-5">
                @foreach ([
                    ['isax-search-normal-1', __('Réserver'), __('Choisissez vos villes et la date, puis un départ. Sur le plan de l\'autocar, cliquez sur un siège libre et confirmez.'), route('voyages.list'), __('Chercher un voyage')],
                    ['isax-wallet-3', __('Payer'), __('À l\'embarquement ou en agence selon le mode choisi, ou par carte bancaire sur la page sécurisée du CMI quand ce mode est proposé.'), null, null],
                    ['isax-ticket', __('Votre billet'), __('Il est envoyé par e-mail et disponible dans votre espace. La veille du départ, vous recevez un rappel.'), route('client.profile.reservations.index'), __('Mes réservations')],
                    ['isax-close-circle', __('Annuler'), __('Depuis votre espace, jusqu\'au départ du bus : le siège est libéré et vous recevez une confirmation. Le remboursement dépend du délai (voir le tableau).'), route('client.profile.reservations.index'), __('Gérer mes billets')],
                ] as [$icon, $title, $text, $link, $label])
                    <div class="col-lg-3 col-md-6">
                        <div class="mz-step">
                            <span class="mz-step-icon"><i class="isax {{ $icon }}"></i></span>
                            <h5>{{ $title }}</h5>
                            <p class="mb-3">{{ $text }}</p>
                            @if ($link)
                                <a href="{{ $link }}" class="fw-medium fs-14">{{ $label }} <i class="isax isax-arrow-right-3"></i></a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row justify-content-center mb-5" id="annulation">
                <div class="col-xl-8 col-lg-10">
                    <div class="section-header section-header-four text-center mb-4">
                        <h2 class="mb-2">{!! __('<span>Annulation</span> et remboursement') !!}</h2>
                    </div>
                    <div class="card border-0 shadow-sm"><div class="card-body">@include('partials.refund-policy')</div></div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-10">
                    <div class="section-header section-header-four text-center mb-4">
                        <h2 class="mb-2">{!! __('<span>Questions</span> fréquentes') !!}</h2>
                    </div>
                    @include('partials.faq')

                    <div class="text-center mt-5">
                        <p class="text-muted mb-2">{{ __('Vous ne trouvez pas la réponse ?') }}</p>
                        <a href="{{ route('contact') }}" class="btn btn-primary">{{ __('Contactez-nous') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

<x-guest-layout>
    <div class="main-wrapper authentication-wrapper">
        <div class="container-fuild">
            <div class="w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
                <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap">
                    <div class="col-xxl-4 col-lg-6 col-md-6 col-11 mx-auto">
                        <div class="p-4 d-flex justify-content-center">
                            <a href="{{ route('home') }}"><img src="{{ \App\Support\BrandImages::url('blasti-logo.png') }}" style="width: 170px" alt="Blasti" class="img-fluid mz-logo-light"><img src="{{ \App\Support\BrandImages::url('blasti-logo-dark.png') }}" style="width: 170px" alt="Blasti" class="img-fluid mz-logo-dark"></a>
                        </div>
                        <div class="card authentication-card">
                            <div class="card-header">
                                <div class="text-center">
                                    <span class="avatar avatar-lg rounded-circle bg-primary-transparent text-primary mb-2"><i class="isax isax-sms-tracking fs-24"></i></span>
                                    <h5 class="mb-1">{{ __('Vérifiez votre adresse e-mail') }}</h5>
                                    <p class="mb-0">{{ __('Nous avons envoyé un lien à :email. Cliquez dessus pour activer votre compte : il faut une adresse vérifiée pour réserver, car vos billets y sont envoyés.', ['email' => auth()->user()->email]) }}</p>
                                </div>
                            </div>
                            <div class="card-body">
                                @if (session('status') == 'verification-link-sent')
                                    <div class="alert alert-success fs-14">{{ __('Un nouveau lien de vérification vient de vous être envoyé.') }}</div>
                                @endif

                                <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
                                    @csrf
                                    <button type="submit" class="btn btn-xl btn-primary w-100">{{ __('Renvoyer le lien de vérification') }}</button>
                                </form>

                                <div class="d-flex justify-content-between align-items-center fs-14">
                                    <a href="{{ route('home') }}" class="link-primary fw-medium">{{ __('Retour à l\'accueil') }}</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-link p-0 fs-14 text-muted">{{ __('Se déconnecter') }}</button>
                                    </form>
                                </div>
                                <p class="text-muted fs-13 mt-3 mb-0">{{ __('Rien reçu ? Regardez dans les courriers indésirables (spam).') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>

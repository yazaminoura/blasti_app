{{--
    Sign in / sign up popup for guests (public pages).
    - opens by itself after config('safar.popup_connexion_secondes') seconds, once per browser session (it can be closed)
    - opened by the seat map when a guest picks a seat: window.BlastiAuth.open('login' | 'register', message)
    - both forms come back to the page the guest was on (redirect_to), the seat map included
--}}
@guest
@php
    $authForm = old('auth_form');
    $retour = url()->full();
@endphp
<div class="modal fade" id="blasti-auth-modal" tabindex="-1" aria-labelledby="blasti-auth-title" aria-hidden="true"
     data-auto-seconds="{{ (int) config('safar.popup_connexion_secondes') }}" data-reopen="{{ $authForm ? $authForm : '' }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0">
                <img src="{{ \App\Support\BrandImages::url('blasti-logo.png') }}" alt="{{ config('safar.nom') }}" style="height: 40px;" class="mz-logo-light">
                <img src="{{ \App\Support\BrandImages::url('blasti-logo-dark.png') }}" alt="{{ config('safar.nom') }}" style="height: 40px;" class="mz-logo-dark">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Fermer') }}"></button>
            </div>
            <div class="modal-body pt-2">
                <h5 class="mb-1" id="blasti-auth-title">{{ __('Bienvenue sur :brand', ['brand' => config('safar.nom')]) }}</h5>
                <p class="text-muted fs-14 mb-3" data-auth-message data-default="{{ __('Connectez-vous ou créez un compte gratuit pour réserver vos sièges et recevoir vos billets par e-mail.') }}">{{ __('Connectez-vous ou créez un compte gratuit pour réserver vos sièges et recevoir vos billets par e-mail.') }}</p>

                <ul class="nav nav-pills nav-justified bg-light rounded-pill p-1 mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill {{ $authForm !== 'register' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#blasti-auth-login" type="button" role="tab">{{ __('Connexion') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill {{ $authForm === 'register' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#blasti-auth-register" type="button" role="tab">{{ __('Inscription') }}</button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Sign in --}}
                    <div class="tab-pane fade {{ $authForm !== 'register' ? 'show active' : '' }}" id="blasti-auth-login" role="tabpanel">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <input type="hidden" name="auth_form" value="login">
                            <input type="hidden" name="redirect_to" value="{{ $retour }}">
                            <div class="mb-3">
                                <label class="form-label" for="auth-login-email">{{ __('Adresse e-mail') }}</label>
                                <input type="email" id="auth-login-email" name="email" class="form-control" value="{{ $authForm === 'login' ? old('email') : '' }}" placeholder="{{ __('Entrez votre adresse e-mail') }}" required autocomplete="email">
                                @if ($authForm === 'login') <x-input-error :messages="$errors->get('email')" class="mt-1" /> @endif
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="auth-login-password">{{ __('Mot de passe') }}</label>
                                <div class="input-icon"><input type="password" id="auth-login-password" name="password" class="form-control pass-input" placeholder="{{ __('Entrez votre mot de passe') }}" required autocomplete="current-password"><span class="input-icon-addon toggle-password" role="button" aria-label="{{ __('Afficher le mot de passe') }}"><i class="isax isax-eye-slash"></i></span></div>
                                @if ($authForm === 'login') <x-input-error :messages="$errors->get('password')" class="mt-1" /> @endif
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="auth-remember">
                                    <label class="form-check-label fs-14" for="auth-remember">{{ __('Se souvenir de moi') }}</label>
                                </div>
                                <a href="{{ route('password.request') }}" class="link-primary fs-14">{{ __('Mot de passe oublié ?') }}</a>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">{{ __('Se connecter') }}</button>
                        </form>
                    </div>

                    {{-- Sign up: a verification link is e-mailed, booking is possible once it is clicked --}}
                    <div class="tab-pane fade {{ $authForm === 'register' ? 'show active' : '' }}" id="blasti-auth-register" role="tabpanel">
                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            <input type="hidden" name="auth_form" value="register">
                            <input type="hidden" name="redirect_to" value="{{ $retour }}">
                            <div class="mb-3">
                                <label class="form-label" for="auth-reg-name">{{ __('Nom complet') }}</label>
                                <input type="text" id="auth-reg-name" name="name" class="form-control" value="{{ $authForm === 'register' ? old('name') : '' }}" required autocomplete="name">
                                @if ($authForm === 'register') <x-input-error :messages="$errors->get('name')" class="mt-1" /> @endif
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="auth-reg-email">{{ __('Adresse e-mail') }}</label>
                                <input type="email" id="auth-reg-email" name="email" class="form-control" value="{{ $authForm === 'register' ? old('email') : '' }}" required autocomplete="email">
                                @if ($authForm === 'register') <x-input-error :messages="$errors->get('email')" class="mt-1" /> @endif
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label" for="auth-reg-password">{{ __('Mot de passe') }}</label>
                                    <div class="input-icon"><input type="password" id="auth-reg-password" name="password" class="form-control pass-input" required minlength="8" autocomplete="new-password"><span class="input-icon-addon toggle-password" role="button" aria-label="{{ __('Afficher le mot de passe') }}"><i class="isax isax-eye-slash"></i></span></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="auth-reg-password2">{{ __('Confirmation') }}</label>
                                    <div class="input-icon"><input type="password" id="auth-reg-password2" name="password_confirmation" class="form-control pass-input" required minlength="8" autocomplete="new-password"><span class="input-icon-addon toggle-password" role="button" aria-label="{{ __('Afficher le mot de passe') }}"><i class="isax isax-eye-slash"></i></span></div>
                                </div>
                                @if ($authForm === 'register') <div class="col-12"><x-input-error :messages="$errors->get('password')" /></div> @endif
                            </div>
                            <button type="submit" class="btn btn-primary w-100">{{ __('Créer mon compte') }}</button>
                            <p class="text-muted fs-13 mt-2 mb-0">{{ __('Vous recevrez un e-mail pour confirmer votre adresse avant de réserver.') }}</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const el = document.getElementById('blasti-auth-modal');
        if (!el || typeof bootstrap === 'undefined') return;
        const modal = bootstrap.Modal.getOrCreateInstance(el);
        const message = el.querySelector('[data-auth-message]');
        const KEY = 'blasti_auth_popup_vu';

        function remember() { try { sessionStorage.setItem(KEY, '1'); } catch (e) {} }
        function alreadySeen() { try { return sessionStorage.getItem(KEY) === '1'; } catch (e) { return false; } }

        window.BlastiAuth = {
            open(tab, text) {
                message.textContent = text || message.dataset.default;
                const button = el.querySelector('[data-bs-target="#blasti-auth-' + (tab === 'register' ? 'register' : 'login') + '"]');
                if (button) bootstrap.Tab.getOrCreateInstance(button).show();
                remember();
                modal.show();
            },
        };

        // a popup form came back with errors: show them again
        if (el.dataset.reopen) {
            remember();
            modal.show();
            return;
        }

        // once per visit, after N seconds, unless another popup is already open
        const seconds = parseInt(el.dataset.autoSeconds, 10) || 0;
        if (seconds > 0 && !alreadySeen()) {
            setTimeout(function () {
                if (alreadySeen() || document.querySelector('.modal.show')) return;
                window.BlastiAuth.open('register');
            }, seconds * 1000);
        }
    })();
</script>
@endguest

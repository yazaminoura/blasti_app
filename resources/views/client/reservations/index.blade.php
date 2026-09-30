<x-app-layout>
    <!-- Breadcrumb -->
    <div class="breadcrumb-bar breadcrumb-bg-05 text-center">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-12">
                    <h2 class="breadcrumb-title mb-2">{{ __($depart->ville->ville) }} → {{ __($arrivee->ville->ville) }}</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="isax isax-home5"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('voyages.list') }}">{{ __('Voyages') }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ __('Réserver') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- /Breadcrumb -->

    <!-- Page Wrapper -->
    <div class="content">
        <div class="container">

            <div class="row">
                <div class="col-xl-8">
                    <!-- Slider -->
                    <div>
                        <div class="service-wrap slider-wrap-five mb-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                                <div class="mb-2">
                                    <h4 class="mb-1 d-flex align-items-center flex-wrap">
                                        {{ $voyage->autocar->societe->raison_social }}
                                    </h4>
                                    @if ($voyage->autocar?->image)
                                        <img src="{{ asset('storage/' . $voyage->autocar->image) }}" alt="{{ __('Autocar :matricule', ['matricule' => $voyage->autocar->matricule]) }}"
                                             class="rounded-3 mt-2 w-100" style="max-height: 260px; object-fit: cover;">
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Slider -->

                    <div class="card shadow-none bg-light-200">
                        <div class="card-body pb-1">
                            <h5 class="d-flex align-items-center fs-18 mb-3">
                                <span class="avatar avatar-md rounded-circle bg-primary me-2"><i
                                        class="isax isax-routing-2"></i></span>
                                {{ __('Itinéraire du bus') }}
                            </h5>
                            {{-- Every stop of the voyage; the client's part of the trip is highlighted --}}
                            <ol class="mz-route list-unstyled mb-3">
                                @foreach ($voyage->arrets as $arret)
                                    @php
                                        $inTrip = $arret->ordre >= $depart->ordre && $arret->ordre <= $arrivee->ordre;
                                        $role = $arret->is($depart) ? __('Montée') : ($arret->is($arrivee) ? __('Descente') : null);
                                    @endphp
                                    <li class="mz-route-stop {{ $inTrip ? 'is-in' : '' }} {{ $role ? 'is-end' : '' }}">
                                        <span class="mz-route-time">{{ $arret->passage_at->format('H:i') }}</span>
                                        <span class="mz-route-dot"></span>
                                        <span class="mz-route-city">
                                            {{ __($arret->ville->ville) }}
                                            @if ($role)<span class="badge bg-primary ms-1">{{ $role }}</span>@endif
                                        </span>
                                        @if ($arret->passage_at->toDateString() !== $voyage->arrets->first()->passage_at->toDateString())
                                            <span class="text-muted fs-12">{{ $arret->passage_at->translatedFormat('d M') }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="mb-3">
                                        <h6 class="mb-1">{{ __('Durée de votre trajet') }}</h6>
                                        @php $minutes = $depart->passage_at->diffInMinutes($arrivee->passage_at); @endphp
                                        <p>{{ intdiv($minutes, 60) }} h {{ str_pad($minutes % 60, 2, '0', STR_PAD_LEFT) }}</p>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="mb-3">
                                        <h6 class="mb-1">{{ __('Autocar') }}</h6>
                                        <p>{{ $voyage->autocar?->matricule }} · {{ __(':n places', ['n' => $voyage->autocar?->nbr_siege]) }}</p>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="mb-3">
                                        <h6 class="mb-1">{{ __('Prix de votre trajet') }}</h6>
                                        <p class="fw-semibold text-primary">{{ number_format($prix, 2, ',', ' ') }} {{ __('DH') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Seat map: in the middle of the page, the checkboxes belong to #booking-form (sidebar) -->
                    <div class="card shadow-none border mb-4" id="seat-map-card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <h5 class="d-flex align-items-center fs-18 mb-0">
                                    <span class="avatar avatar-md rounded-circle bg-primary me-2"><i class="isax isax-ticket"></i></span>
                                    {{ __('Choisissez vos sièges') }}
                                </h5>
                                <span class="badge bg-primary-transparent text-primary fs-13 fw-medium px-3 py-2 rounded-pill">
                                    {{ __(':free places libres', ['free' => max(0, (int) $voyage->autocar?->nbr_siege - count($reservedSeats))]) }} · {{ __(':max sièges maximum', ['max' => config('safar.max_sieges')]) }}
                                </span>
                            </div>
                            {{-- full bus: e-mail when a seat frees up (AlertePlace, sent from Reservation::cancel) --}}
                            @if (count($reservedSeats) >= (int) $voyage->autocar?->nbr_siege)
                                <form method="POST" action="{{ route('client.reservations.alerte', $voyage) }}" class="bl-full-alert mb-3">
                                    @csrf
                                    <input type="hidden" name="de" value="{{ $depart->id }}">
                                    <input type="hidden" name="a" value="{{ $arrivee->id }}">
                                    <i class="isax isax-notification-bing fs-24"></i>
                                    <div class="flex-fill">
                                        <strong class="d-block">{{ __('Ce bus est complet.') }}</strong>
                                        <span class="fs-14">{{ __('Des places se libèrent souvent (annulations). Laissez votre e-mail : nous vous prévenons dès qu\'un siège est libre.') }}</span>
                                        <div class="d-flex gap-2 mt-2">
                                            <input type="email" name="email" class="form-control" required maxlength="120" value="{{ auth()->user()?->email }}" placeholder="{{ __('Votre adresse e-mail') }}">
                                            <button class="btn btn-primary text-nowrap">{{ __('Prévenez-moi') }}</button>
                                        </div>
                                    </div>
                                </form>
                            @endif
                            @include('client.reservations._seat-map', [
                                'totalSeats' => $voyage->autocar?->nbr_siege ?? 0,
                                'reservedSeats' => $reservedSeats,
                                'form' => 'booking-form',
                                'selected' => old('seats', []),
                            ])
                        </div>
                    </div>
                    <div class="accordion custom-accordion accordion-shadow-none">
                        <div class="accordion-item mb-0 border-0 pb-1">
                            <div class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#accordion_collapse_three" aria-expanded="true">
                                    {{ __('Équipements et services à bord') }}
                                </button>
                            </div>
                            <div id="accordion_collapse_three" class="accordion-collapse collapse show">
                                <div class="accordion-body pt-0">
                                    @php $aBord = $equipements->pluck('equipement')->merge($voyage->autocar?->options?->pluck('option') ?? [])->unique(); @endphp
                                    @if ($aBord->isEmpty())
                                        <p class="text-muted fs-14 mb-0">{{ __('La société n\'a pas encore renseigné les équipements de cet autocar.') }}</p>
                                    @else
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($aBord as $item)
                                                <span class="badge bg-primary-transparent text-primary fs-13 fw-medium px-3 py-2 rounded-pill"><i class="isax isax-tick-circle me-1"></i>{{ __($item) }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>




                    </div>
                </div>
                <div class="col-xl-4 theiaStickySidebar">
                    <div class="card shadow-none">
                        <div class="card-body">
                            <h5 class="fs-18 mb-3">{{ __('Votre trajet') }}</h5>
                            {{-- Changing a stop reloads the page: seats and price depend on the segment --}}
                            <form method="GET" action="{{ route('client.reservations.show', $voyage) }}" class="mb-3" id="segment-form">
                                <div class="form-item border rounded p-3 mb-2 w-100">
                                    <label class="form-label fs-14 text-default mb-1" for="seg-de">{{ __('Montée à') }}</label>
                                    <select name="de" id="seg-de" class="form-select border-0 p-0 fw-semibold fs-16" onchange="this.form.submit()" data-bl-select data-bl-icon="isax-location">
                                        @foreach ($voyage->arrets->slice(0, -1) as $arret)
                                            @if ($arret->passage_at->isFuture())
                                                <option value="{{ $arret->id }}" @selected($arret->is($depart))>{{ __($arret->ville->ville) }} · {{ $arret->passage_at->format('H:i') }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-item border rounded p-3 w-100">
                                    <label class="form-label fs-14 text-default mb-1" for="seg-a">{{ __('Descente à') }}</label>
                                    <select name="a" id="seg-a" class="form-select border-0 p-0 fw-semibold fs-16" onchange="this.form.submit()" data-bl-select data-bl-icon="isax-location-tick">
                                        @foreach ($voyage->arrets->where('ordre', '>', $depart->ordre) as $arret)
                                            <option value="{{ $arret->id }}" @selected($arret->is($arrivee))>{{ __($arret->ville->ville) }} · {{ $arret->passage_at->format('H:i') }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <noscript><button class="btn btn-light btn-sm mt-2">{{ __('Mettre à jour') }}</button></noscript>
                            </form>
                            <div class="banner-form">
                                {{-- step 1 of 2: seats; the payment page (client.reservations.payment) comes next --}}
                                <form action="{{ route('client.reservations.checkout') }}" method="POST"
                                    class="form-info border-0" id="booking-form">
                                    @csrf
                                    <input type="hidden" name="voyage_id" value="{{ $voyage->id }}">
                                    <input type="hidden" name="arret_depart_id" value="{{ $depart->id }}">
                                    <input type="hidden" name="arret_arrivee_id" value="{{ $arrivee->id }}">
                                    @if (session('retour_de'))
                                        <input type="hidden" name="retour_de" value="{{ session('retour_de') }}">
                                    @endif

                                    @if ($errors->any() && ! old('auth_form'))
                                        <div class="alert alert-danger mb-3">
                                            @foreach ($errors->all() as $error)
                                                <div>{{ $error }}</div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-3">
                                        <div>
                                            <div class="fs-13 text-muted">{{ __('Départ') }}</div>
                                            <div class="fw-semibold">{{ __(':date à :heure', ['date' => $depart->passage_at->translatedFormat('D d M'), 'heure' => $depart->passage_at->format('H:i')]) }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fs-13 text-muted">{{ __('Prix') }}</div>
                                            <div class="fw-bold fs-18 text-primary">{{ number_format($prix, 2, ',', ' ') }} {{ __('DH') }}</div>
                                        </div>
                                    </div>

                                    @auth
                                        @unless (auth()->user()->hasVerifiedEmail())
                                            {{-- signed up but the link in the e-mail is not clicked yet: no booking until then --}}
                                            @php $boiteMail = \App\Support\Mailbox::for(auth()->user()->email); @endphp
                                            <div class="bl-verify-box mb-3" id="verify-email-alert">
                                                <div class="d-flex gap-2 mb-2">
                                                    <i class="isax isax-sms-tracking fs-20"></i>
                                                    <div>
                                                        <strong class="d-block">{{ __('Confirmez votre adresse e-mail pour réserver.') }}</strong>
                                                        <span>{{ __('Nous avons envoyé un lien à :email. Cliquez dessus, puis revenez sur cette page.', ['email' => auth()->user()->email]) }}</span>
                                                    </div>
                                                </div>
                                                @if (session('status') === 'verification-link-sent')
                                                    <div class="text-success fw-semibold mb-2"><i class="isax isax-tick-circle me-1"></i>{{ __('Un nouveau lien de vérification vient de vous être envoyé.') }}</div>
                                                @endif
                                                <div class="d-flex flex-wrap gap-2">
                                                    @if ($boiteMail)
                                                        <a href="{{ $boiteMail['url'] }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm rounded-pill px-3"><i class="isax isax-sms me-1"></i>{{ __('Ouvrir :boite', ['boite' => $boiteMail['nom']]) }}</a>
                                                    @endif
                                                    @unless (session('status') === 'verification-link-sent')
                                                        <button type="submit" form="resend-verification" class="btn btn-light btn-sm rounded-pill px-3">{{ __('Renvoyer le lien') }}</button>
                                                    @endunless
                                                </div>
                                            </div>
                                        @endunless
                                    @endauth

                                    <!-- Summary of the chosen seats -->
                                    <div class="border rounded p-3 mb-2" id="seat-summary">
                                        <div class="d-flex justify-content-between fs-14">
                                            <span class="text-muted">{{ __('Sièges choisis') }}</span>
                                            <strong id="seat-list">—</strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <span class="text-muted fs-14"><span id="seat-count">0</span> × {{ number_format($prix, 2, ',', ' ') }} {{ __('DH') }}</span>
                                            <strong class="fs-18 text-primary"><span id="seat-total">0,00</span> {{ __('DH') }}</strong>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <button type="submit" class="btn btn-confirm mb-3" id="confirm-booking">
                                        {{ __('Continuer vers le paiement') }} <i class="isax isax-arrow-right-3 ms-1"></i>
                                    </button>
                                    <p class="text-muted fs-13 text-center mb-0"><i class="isax isax-shield-tick me-1"></i>{{ __('Vous choisirez votre mode de paiement à l\'étape suivante.') }}</p>
                                </form>
                                @auth
                                    @unless (auth()->user()->hasVerifiedEmail())
                                        <form method="POST" action="{{ route('verification.send') }}" id="resend-verification" class="d-none">@csrf</form>
                                    @endunless
                                @endauth

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
    <script>
        (function () {
            const inputs = Array.from(document.querySelectorAll('.seat-input'));
            const form = document.getElementById('confirm-booking').form;
            const MAX = {{ (int) config('safar.max_sieges') }};
            const PRICE = {{ (float) $prix }};
            const GUEST = @json(auth()->guest());
            const VERIFIED = @json(auth()->check() && auth()->user()->hasVerifiedEmail());
            @php
                // texts of the popups, built here: Blade's @json() splits its argument on commas
                $textes = [
                    'login' => __('Connectez-vous ou créez un compte pour choisir votre siège. Vous reviendrez ici ensuite.'),
                    'maxTitle' => __('Maximum atteint'),
                    'max' => __('Vous pouvez réserver :max sièges à la fois. Retirez un siège pour en choisir un autre, ou faites une deuxième réservation.', ['max' => config('safar.max_sieges')]),
                    'noneTitle' => __('Aucun siège choisi'),
                    'none' => __('Cliquez sur un siège libre du plan de l\'autocar pour le sélectionner, puis continuez.'),
                    'verifyTitle' => __('Confirmez votre adresse e-mail'),
                    'verify' => __('Pour réserver, cliquez d\'abord sur le lien que nous avons envoyé à :email. Pensez à regarder dans les spams, puis revenez sur cette page.', ['email' => auth()->user()?->email]),
                    'resend' => __('Renvoyer le lien'),
                    'later' => __('Plus tard'),
                ];
                $boite = \App\Support\Mailbox::for(auth()->user()?->email);
                $mailboxLink = $boite ? ['href' => $boite['url'], 'label' => __('Ouvrir :boite', ['boite' => $boite['nom']]), 'icon' => 'isax-sms', 'newTab' => true, 'primary' => true] : null;
            @endphp
            const T = @json($textes);
            const MAILBOX = @json($mailboxLink);
            const fmt = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            function refresh() {
                const chosen = inputs.filter(i => i.checked);
                inputs.forEach(i => i.closest('.bl-seat').classList.toggle('is-selected', i.checked));
                document.getElementById('seat-list').textContent = chosen.length ? chosen.map(i => i.value).join(', ') : '—';
                document.getElementById('seat-count').textContent = chosen.length;
                document.getElementById('seat-total').textContent = fmt.format(chosen.length * PRICE);
            }

            function warn(title, text) {
                if (window.BlastiAlert) BlastiAlert.fire({ type: 'warning', title: title, text: text });
                else alert(text);
            }

            // e-mail not confirmed yet: open the mailbox, or get a new link
            function askVerification() {
                if (!window.BlastiAlert) return alert(T.verify);
                BlastiAlert.fire({
                    type: 'info', icon: 'isax-sms-tracking5', title: T.verifyTitle, text: T.verify,
                    links: MAILBOX ? [MAILBOX] : [],
                    confirmText: T.resend, cancelText: T.later,
                }).then((r) => { if (r.isConfirmed) document.getElementById('resend-verification')?.submit(); });
            }

            inputs.forEach(input => input.addEventListener('click', function (e) {
                // guests never get past the seat choice: sign in / sign up first (the popup can be closed)
                if (GUEST) {
                    e.preventDefault();
                    if (window.BlastiAuth) window.BlastiAuth.open('login', T.login);
                    else window.location = @json(route('login'));
                    return;
                }
                if (this.checked && inputs.filter(i => i.checked).length > MAX) {
                    e.preventDefault();
                    warn(T.maxTitle, T.max);
                    return;
                }
                refresh();
            }));

            form.addEventListener('submit', function (e) {
                if (GUEST) {
                    e.preventDefault();
                    if (window.BlastiAuth) window.BlastiAuth.open('login', T.login);
                    return;
                }
                if (!VERIFIED) {
                    e.preventDefault();
                    askVerification();
                    return;
                }
                if (!inputs.some(i => i.checked)) {
                    e.preventDefault();
                    warn(T.noneTitle, T.none);
                    return;
                }
                const button = document.getElementById('confirm-booking');
                button.disabled = true;
                button.textContent = @json(__('Chargement...'));
            });

            refresh();
        })();
    </script>
</x-app-layout>

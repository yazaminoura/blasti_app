<x-app-layout>
    <x-profile-layout>
        @php
            $d = fn ($date) => \Carbon\Carbon::parse($date)->locale(app()->getLocale())->isoFormat('ddd D MMM YYYY');
            $h = fn ($time) => \Carbon\Carbon::parse($time)->format('H:i');
        @endphp
        <div class="col-xl-9 col-lg-8">

            {{-- Greeting --}}
            <div class="mz-welcome mb-4">
                <div>
                    <h4 class="mb-1">{{ __('Bonjour :name', ['name' => \Illuminate\Support\Str::before($user->name, ' ')]) }} 👋</h4>
                    <p class="mb-0">{{ __('Retrouvez vos billets, vos prochains départs et vos informations.') }}</p>
                </div>
                <a href="{{ route('voyages.list') }}" class="btn btn-light fw-medium"><i class="isax isax-search-normal me-1"></i> {{ __('Réserver un voyage') }}</a>
            </div>

            {{-- Numbers --}}
            <div class="row g-3 mb-4">
                @foreach ([
                    [__('Voyages à venir'), $stats['upcoming'], 'isax-calendar-tick5', 'primary'],
                    [__('Voyages effectués'), $stats['done'], 'isax-bus', 'success'],
                    [__('Total dépensé'), number_format($stats['spent'], 0, ',', ' ') . ' DH', 'isax-wallet-3', 'warning'],
                    [__('Favoris'), $stats['favorites'], 'isax-heart5', 'danger'],
                ] as [$label, $value, $icon, $tone])
                    <div class="col-6 col-xl-3">
                        <div class="mz-stat">
                            <span class="mz-stat-icon text-{{ $tone }} bg-{{ $tone }}-transparent"><i class="isax {{ $icon }}"></i></span>
                            <div>
                                <div class="mz-stat-value">{{ $value }}</div>
                                <div class="mz-stat-label">{{ $label }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Next trip --}}
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fs-17">{{ __('Votre prochain départ') }}</h5>
                    @if ($nextTrip)
                        <span class="badge bg-success rounded-pill">{{ \Carbon\Carbon::parse($nextTrip->date_depart)->isToday() ? __("Aujourd'hui") : \Carbon\Carbon::parse($nextTrip->date_depart)->locale(app()->getLocale())->diffForHumans(['parts' => 1]) }}</span>
                    @endif
                </div>
                <div class="card-body">
                    @if ($nextTrip)
                        <div class="mz-trip">
                            <div>
                                <div class="mz-trip-city">{{ __($nextTrip->villeDepart?->ville) }}</div>
                                <div class="mz-trip-when">{{ $d($nextTrip->date_depart) }} · {{ $h($nextTrip->heure_depart) }}</div>
                            </div>
                            <div class="mz-trip-line"><i class="isax isax-bus"></i></div>
                            <div class="text-end">
                                <div class="mz-trip-city">{{ __($nextTrip->villeArrivee?->ville) }}</div>
                                <div class="mz-trip-when">{{ $d($nextTrip->date_arrivee) }} · {{ $h($nextTrip->heure_arrivee) }}</div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                            <div class="d-flex flex-wrap gap-4 fs-14">
                                <span><span class="text-muted">{{ __('Siège') }}</span> <strong>{{ __('N° :num', ['num' => $nextTrip->num_siege]) }}</strong></span>
                                <span><span class="text-muted">{{ __('Société') }}</span> <strong>{{ $nextTrip->autocar?->societe?->raison_social ?? '—' }}</strong></span>
                                <span><span class="text-muted">{{ __('Billet') }}</span> <strong>#{{ $nextTrip->id }}</strong></span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('ticket.show', $nextTrip->id) }}" class="btn btn-outline-primary btn-sm"><i class="isax isax-eye me-1"></i> {{ __('Voir le billet') }}</a>
                                <a href="{{ route('ticket.download', $nextTrip->id) }}" class="btn btn-primary btn-sm"><i class="isax isax-document-download me-1"></i> PDF</a>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="isax isax-bus text-primary" style="font-size: 2.5rem;"></i>
                            <p class="text-muted mt-2 mb-3">{{ __('Aucun départ prévu. Où allez-vous ensuite ?') }}</p>
                            <a href="{{ route('voyages.list') }}" class="btn btn-primary">{{ __('Voir les voyages') }}</a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="row g-4">
                {{-- Latest bookings --}}
                <div class="col-xl-7">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0 fs-17">{{ __('Dernières réservations') }}</h5>
                            <a href="{{ route('client.profile.reservations.index') }}" class="fs-14 fw-medium">{{ __('Tout voir') }}</a>
                        </div>
                        <div class="card-body p-0">
                            @forelse ($recent as $r)
                                <a href="{{ route('ticket.show', $r->id) }}" class="mz-list-item">
                                    <span class="mz-stat-icon bg-primary-transparent text-primary"><i class="isax isax-ticket"></i></span>
                                    <span class="flex-grow-1 min-w-0">
                                        <span class="d-block fw-medium text-dark text-truncate">{{ __($r->villeDepart?->ville) }} → {{ __($r->villeArrivee?->ville) }}</span>
                                        <span class="d-block fs-13 text-muted">{{ $d($r->date_depart) }} · {{ __('siège :num', ['num' => $r->num_siege]) }}</span>
                                    </span>
                                    <span class="fw-semibold text-dark text-nowrap">{{ number_format($r->prix + $r->frais, 0, ',', ' ') }} DH</span>
                                </a>
                            @empty
                                <p class="text-muted text-center py-4 mb-0">{{ __('Pas encore de réservation.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Account --}}
                <div class="col-xl-5">
                    <div class="card h-100">
                        <div class="card-header"><h5 class="mb-0 fs-17">{{ __('Mon compte') }}</h5></div>
                        <div class="card-body">
                            @if ($missing->isNotEmpty())
                                <div class="alert alert-warning d-flex gap-2 fs-14">
                                    <i class="isax isax-info-circle mt-1"></i>
                                    <div>{{ __('Complétez votre profil (:champs) pour que la société puisse vous joindre en cas de changement.', ['champs' => $missing->map(fn ($m) => __($m))->join(', ', ' ' . __('et') . ' ')]) }}</div>
                                </div>
                            @endif
                            <div class="d-grid gap-2">
                                <a href="{{ route('client.profile.parametres.index') }}" class="mz-list-item border rounded">
                                    <i class="isax isax-user-edit text-primary fs-18"></i><span class="flex-grow-1">{{ __('Modifier mes informations') }}</span><i class="isax isax-arrow-right-3"></i>
                                </a>
                                <a href="{{ route('client.profile.parametres.index') }}#securite" class="mz-list-item border rounded">
                                    <i class="isax isax-lock-1 text-primary fs-18"></i><span class="flex-grow-1">{{ __('Changer mon mot de passe') }}</span><i class="isax isax-arrow-right-3"></i>
                                </a>
                                <a href="{{ route('contact') }}" class="mz-list-item border rounded">
                                    <i class="isax isax-message-question text-primary fs-18"></i><span class="flex-grow-1">{{ __('Besoin d\'aide ?') }}</span><i class="isax isax-arrow-right-3"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-profile-layout>
</x-app-layout>

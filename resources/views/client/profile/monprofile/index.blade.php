<x-app-layout>
    <x-profile-layout>
        @php
            $u = auth()->user();
            $fields = [
                __('Identité') => [
                    'isax-user' => [__('Nom et prénom'), $u->name],
                    'isax-sms' => [__('Email'), $u->email],
                    'isax-call' => [__('Téléphone'), $u->telephone],
                ],
                __('Adresse') => [
                    'isax-location' => [__('Adresse'), $u->adresse],
                    'isax-buildings' => [__('Ville'), __($u->ville)],
                    'isax-map' => [__('Région'), $u->region],
                    'isax-global' => [__('Pays'), $u->pays],
                    'isax-direct-inbox' => [__('Code postal'), $u->code_postal],
                ],
            ];
            $filled = collect($fields)->flatten(1)->filter(fn ($f) => filled($f[1]))->count();
            $total = collect($fields)->flatten(1)->count();
            $percent = (int) round($filled * 100 / $total);
        @endphp
        <div class="col-xl-9 col-lg-8">
            <div class="card mb-0">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fs-17">{{ __('Mon profil') }}</h5>
                    <a href="{{ route('client.profile.parametres.index') }}" class="btn btn-primary btn-sm"><i class="isax isax-edit-2 me-1"></i> {{ __('Modifier') }}</a>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                        <img src="{{ $u->profile_image_path }}" alt="" class="rounded-circle" style="width: 84px; height: 84px; object-fit: cover;"
                             onerror="this.src='{{ asset('assets/img/users/user-01.jpg') }}'">
                        <div class="flex-grow-1">
                            <h5 class="mb-1">{{ $u->name }}</h5>
                            <div class="text-muted fs-14">{{ __('Membre depuis :date', ['date' => $u->created_at->locale(app()->getLocale())->isoFormat('D MMMM YYYY')]) }}
                                @if ($u->email_verified_at) · <span class="text-success"><i class="isax isax-verify5"></i> {{ __('Email vérifié') }}</span>@endif
                            </div>
                        </div>
                        <div style="min-width: 200px;">
                            <div class="d-flex justify-content-between fs-13 mb-1"><span class="text-muted">{{ __('Profil complété') }}</span><strong>{{ $percent }} %</strong></div>
                            <div class="progress" style="height: 6px;"><div class="progress-bar" style="width: {{ $percent }}%"></div></div>
                        </div>
                    </div>

                    @foreach ($fields as $section => $items)
                        <h6 class="mz-section-title"><i class="isax {{ $loop->first ? 'isax-profile-circle' : 'isax-location' }}"></i> {{ $section }}</h6>
                        <div class="mz-info-grid {{ $loop->last ? '' : 'mb-4' }}">
                            @foreach ($items as $icon => [$label, $value])
                                <div class="mz-info">
                                    <div class="mz-info-label"><i class="isax {{ $icon }} me-1"></i>{{ $label }}</div>
                                    <div class="mz-info-value {{ blank($value) ? 'is-empty' : '' }}">{{ filled($value) ? $value : __('Non renseigné') }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-profile-layout>
</x-app-layout>

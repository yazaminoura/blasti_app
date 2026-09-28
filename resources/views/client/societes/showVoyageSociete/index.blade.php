<x-app-layout>
    <div class="container">
        <h1 class="showVoyageSociete">{{ __('Voyages pour :societe Transport :', ['societe' => $societe->raison_social]) }} </h1>
        <div class="row">
            @forelse ($voyages as $voyage)
                @php
                    $image = $voyage->image ?: $voyage->autocar?->image;
                @endphp
                <div class="col-xxl-4 col-lg-6 col-md-6 col-12 mb-4">
                    <div class="card authentication-card">
                        <div class="card-body">
                            <div class="place-item mb-4">
                                <div class="place-img">
                                    <img src="{{ $image ? asset('storage/' . $image) : asset('assets/img/photo1.png') }}" class="img-fluid" alt="{{ __('img') }}">
                                </div>
                                <div class="place-content">
                                    <div class="flight-loc d-flex align-items-center justify-content-between mb-2">
                                        <span class="loc-name d-inline-flex align-items-center">
                                            <i class="isax isax-airplane rotate-45 me-2"></i>{{ __($voyage->villeDepart?->ville ?? '') }}
                                        </span>
                                        <span class="arrow-icon"><i class="isax isax-arrow-2"></i></span>
                                        <span class="loc-name d-inline-flex align-items-center">
                                            <i class="isax isax-airplane rotate-135 me-2"></i>{{ __($voyage->villeArrivee?->ville ?? '') }}
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-center mb-2">
                                        <p class="fs-14 mb-0">{{ __('matricule de bus : :matricule', ['matricule' => $voyage->autocar?->matricule]) }}</p>
                                        <p class="fs-14 d-inline-flex align-items-center mb-0">
                                            <i class="fa-solid fa-circle fs-6 text-primary mx-2"></i>
                                            {{ __($voyage->typeVoyage?->type_voyage ?? '') }}
                                        </p>
                                    </div>
                                    <div class="date-info p-2 mb-3">
                                        <p class="d-flex align-items-center">
                                            <i class="isax isax-calendar-2 me-2"></i>{{ $voyage->date_depart }} - {{ $voyage->date_arrivee }}
                                        </p>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                        <h6 class="text-primary">
                                            <span class="fs-14 fw-normal text-default">{{ __('prix') }} </span>{{ $voyage->prix }} DH
                                        </h6>
                                        <a href="{{ route('client.reservations.show', $voyage->id) }}" class="btn btn-primary btn-sm">{{ __('Réserver') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div>
                    <h4>{{ __('Aucun voyage trouvé.') }}</h4>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>

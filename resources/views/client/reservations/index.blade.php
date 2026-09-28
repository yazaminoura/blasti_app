<x-app-layout>
    <style>
        .bus-container {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 30px 30px 10px 10px;
            padding: 20px;
            margin: 0 auto;
            position: relative;
            max-width: 280px;
        }

        .bus-front {
            height: 60px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding-left: 10px;
        }

        .steering-wheel {
            width: 40px;
            height: 40px;
            border: 3px solid #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e0e0e0;
        }

        .bus-body {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .seat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .seat-pair {
            display: flex;
            gap: 8px;
        }

        .seat {
            width: 45px;
            height: 45px;
            background-color: #78c43c; /* Available Green */
            color: #fff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
            box-shadow: 0 3px 0 #5a932d;
            border: none;
        }

        .seat::before {
            content: '';
            position: absolute;
            top: -4px;
            left: 5px;
            right: 5px;
            height: 8px;
            background: inherit;
            border-radius: 4px 4px 0 0;
            opacity: 0.8;
        }

        .seat-reserved {
            background-color: #f0f0f0 !important;
            color: #ccc !important;
            box-shadow: 0 3px 0 #d0d0d0 !important;
            cursor: not-allowed;
        }

        .seat-reserved i {
            display: none;
        }

        .seat-reserved::after {
            content: '\2715'; /* X mark */
            font-size: 14px;
            color: #ccc;
        }

        .seat-selected {
            background-color: var(--brand) !important; /* Selected Orange */
            box-shadow: 0 3px 0 var(--brand-700) !important;
        }

        .seat i {
            font-size: 0.8rem;
            display: none; /* Hide icon to match image more closely if needed, or keep small */
        }

        .seat small {
            font-size: 0.85rem;
            font-weight: 600;
        }

        .aisle {
            width: 30px;
        }

        .btn-confirm {
            background-color: var(--brand);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
            box-shadow: 0 4px 0 var(--brand-700);
        }
        
        .btn-confirm:hover {
            background-color: var(--brand-600);
            color: white;
        }
    </style>
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
                                    <select name="de" id="seg-de" class="form-select border-0 p-0 fw-semibold fs-16" onchange="this.form.submit()">
                                        @foreach ($voyage->arrets->slice(0, -1) as $arret)
                                            @if ($arret->passage_at->isFuture())
                                                <option value="{{ $arret->id }}" @selected($arret->is($depart))>{{ __($arret->ville->ville) }} · {{ $arret->passage_at->format('H:i') }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-item border rounded p-3 w-100">
                                    <label class="form-label fs-14 text-default mb-1" for="seg-a">{{ __('Descente à') }}</label>
                                    <select name="a" id="seg-a" class="form-select border-0 p-0 fw-semibold fs-16" onchange="this.form.submit()">
                                        @foreach ($voyage->arrets->where('ordre', '>', $depart->ordre) as $arret)
                                            <option value="{{ $arret->id }}" @selected($arret->is($arrivee))>{{ __($arret->ville->ville) }} · {{ $arret->passage_at->format('H:i') }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <noscript><button class="btn btn-light btn-sm mt-2">{{ __('Mettre à jour') }}</button></noscript>
                            </form>
                            <div class="banner-form">
                                <form action="{{ route('client.reservations.store') }}" method="POST"
                                    class="form-info border-0">
                                    @csrf
                                    <input type="hidden" name="voyage_id" value="{{ $voyage->id }}">
                                    <input type="hidden" name="arret_depart_id" value="{{ $depart->id }}">
                                    <input type="hidden" name="arret_arrivee_id" value="{{ $arrivee->id }}">

                                    @if ($errors->any())
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

                                    <!-- Preferred Class -->
                                    {{-- <div class="mb-3">
                                  <label class="form-label fs-14 text-default mb-1">Preferred Class</label>
                                  <select class="form-select" name="classe_id">
                                    @foreach (App\Models\TypeVoyage::all() as $typevoyage)
                                      <option value="{{ $typevoyage->id }}">{{ $typevoyage->type_voyage }}</option>
                                    @endforeach
                                  </select>
                                </div> --}}

                                    <!-- Mode règlement -->
                                    <div class="mb-3">
                                        <label class="form-label fs-14 text-default mb-1">{{ __('Mode de règlement') }}</label>
                                        <select name="mode_reglement_id" class="form-select" required>
                                            @foreach ($modes as $mode)
                                                <option value="{{ $mode->id }}" @selected(old('mode_reglement_id') == $mode->id)>
                                                    {{ $mode->en_ligne ? __(':mode (carte bancaire, paiement sécurisé CMI)', ['mode' => __($mode->mode_reglement)]) : __(':mode (à régler à l\'embarquement)', ['mode' => __($mode->mode_reglement)]) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @include('partials.refund-policy', ['compact' => true])
                                    </div>

                                    <!-- Seat Selection -->
                                    <div class="card shadow-none mb-3">
                                        <div class="card-body p-3">
                                            <div class="border-bottom pb-2 mb-3">
                                                <h6 onclick="resetCheckboxes()">{{ __('Réservation de siège') }}</h6>
                                            </div>
                                            
                                            <div class="bus-container">
                                                <div class="bus-front">
                                                    <div class="steering-wheel">
                                                        <i class="fa fa-dharmachakra"></i>
                                                    </div>
                                                </div>

                                                <div class="bus-body">
                                                    @php
                                                        $total_seats = (int) ($voyage->autocar?->nbr_siege ?? 0);
                                                        // Classic 2 + 2 layout: row r holds seats 4r+1, 4r+2 | aisle | 4r+3, 4r+4
                                                        $rows = (int) ceil($total_seats / 4);
                                                    @endphp

                                                    @for ($row = 0; $row < $rows; $row++)
                                                        <div class="seat-row">
                                                            @foreach ([[1, 2], [3, 4]] as $side => $offsets)
                                                                @if ($side === 1)
                                                                    <div class="aisle"></div>
                                                                @endif
                                                                <div class="seat-pair">
                                                                    @foreach ($offsets as $offset)
                                                                        @php $seatNum = $row * 4 + $offset; @endphp
                                                                        @if ($seatNum <= $total_seats)
                                                                            @php $isReserved = in_array($seatNum, $reservedSeats); @endphp
                                                                            <div class="seat @if ($isReserved) seat-reserved @endif">
                                                                                <label for="seat-{{ $seatNum }}" class="w-100 h-100 d-flex align-items-center justify-content-center cursor-pointer mb-0">
                                                                                    <input type="checkbox" name="seats[]" id="seat-{{ $seatNum }}"
                                                                                        @if ($isReserved) disabled @endif
                                                                                        class="d-none" value="{{ $seatNum }}"
                                                                                        onclick="toggleCheckboxes(this);">
                                                                                    <small>{{ $seatNum }}</small>
                                                                                </label>
                                                                            </div>
                                                                        @else
                                                                            <div style="width: 45px; height: 45px;"></div>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endfor
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <button type="submit" class="btn btn-confirm mb-3">
                                        {{ __('Confirmer') }}
                                    </button>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
    <script>
        function toggleCheckboxes(selectedCheckbox) {
            const seatDiv = selectedCheckbox.closest('.seat');
            const reservedSeats = @json($reservedSeats);
            
            // Toggle current seat
            if (selectedCheckbox.checked) {
                seatDiv.classList.add('seat-selected');
            } else {
                seatDiv.classList.remove('seat-selected');
            }

            // Single selection logic: uncheck others
            const checkboxes = document.querySelectorAll('input[name="seats[]"]');
            checkboxes.forEach(checkbox => {
                if (checkbox !== selectedCheckbox) {
                    checkbox.checked = false;
                    checkbox.closest('.seat').classList.remove('seat-selected');
                }
            });
        }

        function resetCheckboxes() {
            const checkboxes = document.querySelectorAll('input[name="seats[]"]');
            checkboxes.forEach(checkbox => {
                if (!checkbox.disabled) {
                    checkbox.checked = false;
                    checkbox.closest('.seat').classList.remove('seat-selected');
                }
            });
        }
    </script>
</x-app-layout>

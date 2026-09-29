<x-app-layout>
    <x-profile-layout>
          <!-- Hotel Booking -->
          <div class="col-xl-9 col-lg-8 theiaStickySidebar">

            <!-- Booking Header -->
            {{-- <div class="card booking-header">
                <div class="card-body header-content d-flex align-items-center justify-content-between flex-wrap ">
                    <div>
                        <h6>Flights</h6>
                        <p class="fs-14 text-gray-6 fw-normal ">No of Booking : 40</p>
                    </div>

                    <div class="d-flex align-items-center flex-wrap">
                        <div class="input-icon-start  me-3 position-relative">
                            <span class="icon-addon">
                                <i class="isax isax-calendar-edit fs-14"></i>
                            </span>
                            <input type="text" class="form-control date-range bookingrange" placeholder="Select" value="Academic Year : 2024 / 2025">
                        </div>
                        <div class="dropdown ">
                            <a href="javascript:void(0);" class="dropdown-toggle btn border text-gray-6 rounded  fw-normal fs-14 d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                <i class="ti ti-file-export me-2 fs-14 text-gray-6"></i>Export
                            </a>
                            <ul class="dropdown-menu  dropdown-menu-end p-3">
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-1"></i>Export as Excel</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div> --}}
            <!-- /Booking Header -->

            <!-- Car-Booking List -->
            <div class="card hotel-list">
                <div class="card-body p-0">
                    <div class="list-header d-flex align-items-center justify-content-between flex-wrap">
                        <h6 class="">{{ __('Liste de réservation') }}</h6>
                        {{-- <div class="d-flex align-items-center flex-wrap">
                            <div class="input-icon-start  me-2 position-relative">
                                <span class="icon-addon">
                                <i class="isax isax-search-normal-1 fs-14"></i>
                            </span>
                                <input type="text" class="form-control" placeholder="Search">
                            </div>
                            <div class="dropdown me-3">
                                <a href="javascript:void(0);" class="dropdown-toggle text-gray-6 btn  rounded border d-inline-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                Ticket Type
                            </a>
                                <ul class="dropdown-menu dropdown-menu-end p-3">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Business Class</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Economy</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Fare Economy</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="dropdown me-3">
                                <a href="javascript:void(0);" class="dropdown-toggle text-gray-6 btn  rounded border d-inline-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                Status
                            </a>
                                <ul class="dropdown-menu dropdown-menu-end p-3">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Upcoming</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Pending</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Cancelled</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">Completed</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="d-flex align-items-center sort-by">
                                <span class="fs-14 text-gray-9 fw-medium">Sort By :</span>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle text-gray-6 btn  rounded d-inline-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                Recommended
                                </a>
                                    <ul class="dropdown-menu dropdown-menu-end p-3">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1">Recently Added</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1">Ascending</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1">Desending</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1">Last Month</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1">Last 7 Days</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                        </div> --}}
                    </div>

                    <!-- Reservations -->
                    @if ($reservations->isEmpty())
                        <div class="text-center py-5">
                            <i class="isax isax-ticket text-primary" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 mb-1">{{ __('Vous n\'avez pas encore de réservation') }}</h5>
                            <p class="text-muted mb-3">{{ __('Choisissez un départ et réservez votre siège en quelques clics.') }}</p>
                            <a href="{{ route('voyages.list') }}" class="btn btn-primary">{{ __('Voir les voyages') }}</a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="thead-light">
                                    <tr>
                                        <th>{{ __('Billet') }}</th>
                                        <th>{{ __('Trajet') }}</th>
                                        <th>{{ __('Départ') }}</th>
                                        <th>{{ __('Siège') }}</th>
                                        <th>{{ __('Total') }}</th>
                                        <th>{{ __('Statut') }}</th>
                                        <th class="text-end">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reservations as $reservation)
                                        @php $aVenir = $reservation->date_depart >= today()->toDateString(); @endphp
                                        <tr class="{{ $reservation->isCancelled() ? 'opacity-50' : '' }}">
                                            <td><a href="{{ route('ticket.show', $reservation->id) }}" class="link-primary fw-medium">#{{ $reservation->id }}</a></td>
                                            <td>
                                                <div class="fw-medium">{{ __($reservation->villeDepart?->ville) }} → {{ __($reservation->villeArrivee?->ville) }}</div>
                                                <div class="fs-12 text-muted">{{ $reservation->autocar?->societe?->raison_social }}</div>
                                            </td>
                                            <td>
                                                {{ \Carbon\Carbon::parse($reservation->date_depart)->format('d/m/Y') }}
                                                <div class="fs-12 text-muted">{{ \Carbon\Carbon::parse($reservation->heure_depart)->format('H:i') }}</div>
                                            </td>
                                            <td>{{ __('N° :num', ['num' => $reservation->num_siege]) }}</td>
                                            <td class="text-nowrap">{{ number_format($reservation->prix + $reservation->frais, 2, ',', ' ') }} DH</td>
                                            <td>
                                                @php [$badge, $tone] = $reservation->statusBadge(); $tone = ['muted' => 'secondary', 'danger' => 'danger', 'warning' => 'warning', 'success' => 'success', 'info' => 'info'][$tone]; @endphp
                                                @if (! $reservation->isCancelled() && ! $aVenir)
                                                    <span class="badge badge-secondary rounded-pill fs-10">{{ __('Effectué') }}</span>
                                                @else
                                                    <span class="badge bg-{{ $tone }}-transparent text-{{ $tone }} rounded-pill fs-10">{{ $badge }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-nowrap">
                                                <a href="{{ route('ticket.show', $reservation->id) }}" class="me-2" title="{{ __('Voir le billet') }}"><i class="isax isax-eye"></i></a>
                                                @unless ($reservation->isCancelled())
                                                    <a href="{{ route('ticket.download', $reservation->id) }}" class="me-2" title="{{ __('Télécharger le billet (PDF)') }}"><i class="isax isax-document-download"></i></a>
                                                @endunless
                                                @if ($reservation->canBeChangedByClient())
                                                    <a href="{{ route('client.reservations.change', $reservation->id) }}" class="me-2" title="{{ __('Modifier la date') }}"><i class="isax isax-calendar-edit"></i></a>
                                                @endif
                                                @if ($reservation->canBeCancelledByClient())
                                                    <form method="POST" action="{{ route('client.reservations.cancel', $reservation) }}" class="d-inline" onsubmit="return confirm(@json($reservation->cancelConfirmText()));">
                                                        @csrf
                                                        <button type="submit" class="btn btn-link text-danger p-0 align-baseline" title="{{ __('Annuler le billet') }}"><i class="isax isax-close-circle"></i></button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <!-- /Reservations -->

                </div>
            </div>

            @if ($reservations->hasPages())
                <div class="d-flex justify-content-center mt-3">{{ $reservations->links() }}</div>
            @endif
        </div>
        <!-- /Hotel Booking -->
    </x-profile-layout>

</x-app-layout>

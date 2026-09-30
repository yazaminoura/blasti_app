<x-app-layout>
    <x-profile-layout>
          <!-- Hotel Booking -->
          <div class="col-xl-9 col-lg-8 theiaStickySidebar">

            <!-- Booking Header -->
            <!-- /Booking Header -->

            <!-- Car-Booking List -->
            <div class="card hotel-list">
                <div class="card-body p-0">
                    <div class="list-header d-flex align-items-center justify-content-between flex-wrap">
                        <h6 class="">{{ __('Liste de réservation') }}</h6>
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
                                            <td class="text-nowrap">{{ number_format($reservation->total(), 2, ',', ' ') }} DH</td>
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
                                                @if ($reservation->peutEtreNote())
                                                    <a href="{{ route('client.avis.create', $reservation->id) }}" class="me-2 text-warning" title="{{ __('Donner mon avis') }}"><i class="isax isax-star-1"></i></a>
                                                @endif
                                                @if ($reservation->canBeChangedByClient())
                                                    <a href="{{ route('client.reservations.change', $reservation->id) }}" class="me-2" title="{{ __('Modifier la date') }}"><i class="isax isax-calendar-edit"></i></a>
                                                @endif
                                                @if ($reservation->canBeCancelledByClient())
                                                    <form method="POST" action="{{ route('client.reservations.cancel', $reservation) }}" class="d-inline" data-bl-confirm="{{ __('Annuler ce billet ?') }}" data-bl-confirm-text="{{ $reservation->cancelConfirmText() }}" data-bl-confirm-button="{{ __('Oui, annuler le billet') }}" data-bl-danger>
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

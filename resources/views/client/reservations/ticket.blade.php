<x-app-layout>
    <div class="content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card booking-confirmation mb-0 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <!-- Header with logo and status -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <img src="{{ \App\Support\BrandImages::url('blasti-logo.png') }}" alt="Blasti" style="height: 56px;">
                                <div class="text-end">
                                    @php [$badge, $tone] = $reservation->statusBadge(); $tone = ['muted' => 'secondary'][$tone] ?? $tone; @endphp
                                    @if (! $reservation->isCancelled() && $reservation->date_depart < today()->toDateString())
                                        <span class="badge bg-secondary p-2 rounded-pill">{{ __('Voyage effectué') }}</span>
                                    @else
                                        <span class="badge bg-{{ $tone }} p-2 rounded-pill">{{ $badge }}</span>
                                    @endif
                                    <p class="text-muted mb-0 mt-1">{{ __('Réservation N°: :id', ['id' => $reservation->id]) }}</p>
                                </div>
                            </div>

                            @if ($reservation->isCancelled())
                                <div class="alert alert-danger d-flex gap-2 align-items-start">
                                    <i class="isax isax-close-circle mt-1"></i>
                                    <div>{{ __('Ce billet a été annulé le :date à :heure : il n\'est plus valable pour voyager.', ['date' => $reservation->annulee_le?->format('d/m/Y'), 'heure' => $reservation->annulee_le?->format('H:i')]) }}
                                        @if ($reservation->needsRefund()) {{ __('Remboursement de :montant DH en cours de traitement.', ['montant' => number_format($reservation->amountToRefund(), 2, ',', ' ')]) }} @elseif ($reservation->rembourse_le) {{ __('Remboursé le :date.', ['date' => $reservation->rembourse_le->format('d/m/Y')]) }} @endif
                                    </div>
                                </div>
                            @elseif ($reservation->statut === \App\Models\Reservation::EN_ATTENTE)
                                <div class="alert alert-warning">{{ __('Paiement en cours de confirmation par la banque. Rechargez la page dans un instant.') }}</div>
                            @elseif (! $reservation->isPaid())
                                <div class="alert alert-info">{{ __('Billet confirmé. Le paiement (:montant DH) se fait à l\'embarquement.', ['montant' => number_format($reservation->total(), 2, ',', ' ')]) }}</div>
                                @php $annuleLe = config('safar.confirmation.active') && ! $reservation->modeReglement?->en_agence ? $reservation->cancellationDue() : null; @endphp
                                @if ($annuleLe && $annuleLe->isFuture() && auth()->id() === $reservation->user_id)
                                    {{-- unpaid: cancelled at this time unless paid (config safar.confirmation) --}}
                                    <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
                                        @if ($reservation->awaitsPresence())
                                            <span>{{ __('Payez ou confirmez votre présence avant le :date à :heure, sinon ce billet non payé sera annulé.', ['date' => $annuleLe->translatedFormat('d M'), 'heure' => $annuleLe->format('H:i')]) }}</span>
                                            <a href="{{ $reservation->presenceUrl() }}" class="btn btn-success btn-sm">{{ __('Je confirme ma présence') }}</a>
                                        @else
                                            <span>{{ __('Payez ce billet avant le :date à :heure, sinon il sera annulé.', ['date' => $annuleLe->translatedFormat('d M'), 'heure' => $annuleLe->format('H:i')]) }}</span>
                                        @endif
                                    </div>
                                @endif
                            @endif

                            <!-- Main ticket title -->
                            <div class="text-center mb-4">
                                <h3 class="fw-bold text-primary">{{ __('Billet de Voyage') }}</h3>
                                <div class="divider mx-auto bg-primary" style="height: 3px; width: 80px;"></div>
                            </div>

                            <!-- Travel information -->
                            <div class="ticket-section mb-4">
                                <h5 class="section-title text-primary mb-3">
                                    <i class="fas fa-route me-2"></i>{{ __('Informations de Voyage') }}
                                </h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="info-card bg-light p-3 rounded">
                                            <h6 class="info-label text-muted">{{ __('Départ') }}</h6>
                                            <p class="info-value fw-bold">{{ __($reservation->villeDepart->ville) }}</p>
                                            <p class="info-detail text-muted">
                                                {{ __(':date à :heure', ['date' => \Carbon\Carbon::parse($reservation->date_depart)->format('d/m/Y'), 'heure' => \Carbon\Carbon::parse($reservation->heure_depart)->format('H:i')]) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="info-card bg-light p-3 rounded">
                                            <h6 class="info-label text-muted">{{ __('Arrivée') }}</h6>
                                            <p class="info-value fw-bold">{{ __($reservation->villeArrivee->ville) }}</p>
                                            <p class="info-detail text-muted">
                                                {{ __(':date à :heure', ['date' => \Carbon\Carbon::parse($reservation->date_arrivee)->format('d/m/Y'), 'heure' => \Carbon\Carbon::parse($reservation->heure_arrivee)->format('H:i')]) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="info-card bg-light p-3 rounded">
                                            <h6 class="info-label text-muted">{{ __('Siège') }}</h6>
                                            <p class="info-value fw-bold">{{ $reservation->num_siege }}</p>
                                        </div>
                                    </div>
                                    {{-- which bus: company, plate number, class --}}
                                    <div class="col-md-6 mb-3">
                                        <div class="info-card bg-light p-3 rounded">
                                            <h6 class="info-label text-muted">{{ __('Autocar') }}</h6>
                                            <p class="info-value fw-bold">{{ $reservation->autocar?->societe?->raison_social ?? '—' }}</p>
                                            <p class="info-detail text-muted">
                                                <i class="isax isax-bus me-1"></i>{{ __('Immatriculation') }} : {{ $reservation->autocar?->matricule ?? '—' }}
                                                @if ($reservation->typeVoyage) · {{ __($reservation->typeVoyage->type_voyage) }}@endif
                                            </p>
                                        </div>
                                    </div>
                                    @unless ($reservation->isCancelled())
                                        <div class="col-md-6 mb-3">
                                            <div class="info-card bg-light p-3 rounded d-flex align-items-center gap-3">
                                                <img src="{{ $reservation->qrCodeDataUri(96) }}" width="96" height="96" alt="{{ __('QR code du billet') }}" class="bg-white p-1 rounded">
                                                <p class="text-muted fs-13 mb-0">{{ __('Présentez ce QR code à l\'embarquement : il permet de vérifier votre billet.') }}</p>
                                            </div>
                                        </div>
                                    @endunless
                                </div>

                                {{-- several seats booked together: one ticket each --}}
                                @php $billetsCommande = $reservation->commandeBillets(); @endphp
                                @if ($billetsCommande->count() > 1)
                                    <div class="border rounded p-3">
                                        <h6 class="mb-2">{{ __('Les billets de cette réservation (:count sièges)', ['count' => $billetsCommande->count()]) }}</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($billetsCommande as $billet)
                                                <a href="{{ route('ticket.show', $billet->id) }}" class="btn btn-sm {{ $billet->is($reservation) ? 'btn-primary' : 'btn-light' }} {{ $billet->isCancelled() ? 'text-decoration-line-through' : '' }}">
                                                    {{ __('Siège :num', ['num' => $billet->num_siege]) }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Passenger information -->
                            <div class="ticket-section mb-4">
                                <h5 class="section-title text-primary mb-3">
                                    <i class="fas fa-user me-2"></i>{{ __('Informations du Voyageur') }}
                                </h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="info-card bg-light p-3 rounded">
                                            <h6 class="info-label text-muted">{{ __('Nom') }}</h6>
                                            <p class="info-value fw-bold">{{ $reservation->passager() }}</p>
                                            @if ($reservation->passager() !== $reservation->user->name)<p class="info-detail text-muted">{{ __('Réservé par :nom', ['nom' => $reservation->user->name]) }}</p>@endif
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="info-card bg-light p-3 rounded">
                                            <h6 class="info-label text-muted">{{ __('Email') }}</h6>
                                            <p class="info-value fw-bold">{{ $reservation->user->email }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="info-card bg-light p-3 rounded">
                                            <h6 class="info-label text-muted">{{ __('Téléphone') }}</h6>
                                            <p class="info-value fw-bold">{{ $reservation->user?->telephone ?: __('Non renseigné') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment information -->
                            <div class="ticket-section mb-4">
                                <h5 class="section-title text-primary mb-3">
                                    <i class="fas fa-credit-card me-2"></i>{{ __('Informations de Paiement') }}
                                </h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="info-card bg-light p-3 rounded">
                                            <h6 class="info-label text-muted">{{ __('Mode de paiement') }}</h6>
                                            <p class="info-value fw-bold">{{ __($reservation->modeReglement?->mode_reglement) }}<br><span class="fs-13 fw-normal text-muted">{{ $reservation->statusBadge()[0] }}</span></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="info-card bg-light p-3 rounded">
                                            <h6 class="info-label text-muted">{{ __('Frais') }}</h6>
                                            <p class="info-value fw-bold">{{ number_format($reservation->frais, 2) }} DH</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="info-card bg-light p-3 rounded">
                                            <h6 class="info-label text-muted">{{ __('Total Payé') }}</h6>
                                            <p class="info-value fw-bold">{{ number_format($reservation->total(), 2) }} DH</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Download button -->
                            <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                                @if (auth()->id() === $reservation->user_id)
                                    <a href="{{ route('client.profile.reservations.index') }}" class="btn btn-light btn-lg px-4 py-2"><i class="isax isax-arrow-left-2 me-1"></i> {{ __('Mes réservations') }}</a>
                                @endif
                                @if (auth()->id() === $reservation->user_id && $reservation->peutEtreNote())
                                    <a href="{{ route('client.avis.create', $reservation->id) }}" class="btn btn-warning btn-lg px-4 py-2">
                                        <i class="isax isax-star-1 me-1"></i> {{ __('Donner mon avis') }}
                                    </a>
                                @endif
                                @if (auth()->id() === $reservation->user_id && $reservation->canBeChangedByClient())
                                    <a href="{{ route('client.reservations.change', $reservation->id) }}" class="btn btn-outline-primary btn-lg px-4 py-2">
                                        <i class="isax isax-calendar-edit me-1"></i> {{ __('Modifier la date') }}
                                    </a>
                                @endif
                                @unless ($reservation->isCancelled())
                                    <a href="{{ route('ticket.download', $reservation->id) }}" class="btn btn-primary btn-lg px-4 py-2">
                                        <i class="fas fa-download me-2"></i> {{ __('Télécharger le billet (PDF)') }}
                                    </a>
                                    <a href="{{ \App\Support\WhatsApp::lien($reservation) }}" target="_blank" rel="noopener" class="btn bl-btn-whatsapp btn-lg px-4 py-2">
                                        <i class="fab fa-whatsapp me-2"></i> {{ __('Envoyer sur WhatsApp') }}
                                    </a>
                                @endunless
                                @if (auth()->id() === $reservation->user_id && $reservation->canBeCancelledByClient())
                                    <form method="POST" action="{{ route('client.reservations.cancel', $reservation) }}" onsubmit="return confirm(@json($reservation->cancelConfirmText()));">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-lg px-4 py-2"><i class="isax isax-close-circle me-1"></i> {{ __('Annuler le billet') }}</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .ticket-section {
            border-bottom: 1px dashed #dee2e6;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .section-title {
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }
        .info-label {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        .info-value {
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        .info-detail {
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        .info-card {
            transition: all 0.3s ease;
            height: 100%;
        }
        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .divider {
            background: linear-gradient(90deg, rgba(0,0,0,0) 0%, var(--brand) 50%, rgba(0,0,0,0) 100%);
        }
    </style>
</x-app-layout>
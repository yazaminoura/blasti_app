@extends('admin.Layout.app')
@section('title', 'Réservation #' . $reservation->id)

@section('content')
@php
    $past = $reservation->date_depart < today()->toDateString();
    $fmtDate = fn ($d) => \Carbon\Carbon::parse($d)->format('d/m/Y');
    $fmtTime = fn ($t) => \Carbon\Carbon::parse($t)->format('H:i');
@endphp

<x-admin.page-header :title="'Réservation #' . $reservation->id" :subtitle="'Réservée le ' . $fmtDate($reservation->date_reservation)"
                     :back="route('reservation.admin.index')" backLabel="Réservations">
    @php
        [$badge, $tone] = $reservation->statusBadge();
        $canUpdate = auth()->user()->hasPermission('reservations.update');
    @endphp
    <span class="sa-chip {{ $tone }} dot align-self-center">{{ $badge }}</span>
    @if (! $reservation->isCancelled())
        <a href="{{ route('ticket.download', $reservation->id) }}" class="btn btn-soft"><i class="bi bi-file-earmark-pdf"></i> Billet PDF</a>
    @endif
    @if ($canUpdate && ! $reservation->isCancelled() && $reservation->resteAPayer() > 0)
        <form action="{{ route('reservation.admin.payer', $reservation) }}" method="POST" class="d-inline">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-primary"><i class="bi bi-cash-coin"></i> Marquer payée</button>
        </form>
    @endif
    @if ($canUpdate && $reservation->needsRefund())
        <form action="{{ route('reservation.admin.rembourser', $reservation) }}" method="POST" class="d-inline">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-counterclockwise"></i> Marquer remboursée ({{ number_format($reservation->amountToRefund(), 2, ',', ' ') }} DH)</button>
        </form>
    @endif
    @if (auth()->user()->hasPermission('reservations.delete') && ! $reservation->isCancelled())
        <form action="{{ route('reservation.admin.destroy', $reservation->id) }}" method="POST" class="d-inline"
              onsubmit="confirmDelete(event, this)"
              data-confirm="Annuler la réservation #{{ $reservation->id }} ?"
              data-confirm-text="Le siège {{ $reservation->num_siege }} redeviendra disponible.{{ $reservation->isPaid() ? ' Annulation par la société : le client sera remboursé à 100 % (' . number_format($reservation->total(), 2, ',', ' ') . ' DH).' : '' }}"
              data-confirm-button="Oui, annuler">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger-soft"><i class="bi bi-x-circle"></i> Annuler la réservation</button>
        </form>
    @endif
</x-admin.page-header>

<div class="row g-4">
    <div class="col-lg-8">
        <x-admin.card title="Voyage" icon="bi-signpost-split">
            <x-slot:actions>
                @if ($past)
                    <span class="sa-chip muted">Voyage passé</span>
                @else
                    <span class="sa-chip success dot">À venir</span>
                @endif
            </x-slot:actions>

            <div class="sa-journey mb-4">
                <div>
                    <div class="sa-journey-city">{{ $reservation->villeDepart?->ville }}</div>
                    <div class="sa-sub">{{ $fmtDate($reservation->date_depart) }} · {{ $fmtTime($reservation->heure_depart) }}</div>
                </div>
                <div class="sa-journey-line"><i class="bi bi-bus-front"></i></div>
                <div>
                    <div class="sa-journey-city">{{ $reservation->villeArrivee?->ville }}</div>
                    <div class="sa-sub">{{ $fmtDate($reservation->date_arrivee) }} · {{ $fmtTime($reservation->heure_arrivee) }}</div>
                </div>
            </div>

            <dl class="sa-dl">
                <dt>Siège</dt><dd><span class="sa-chip brand">N° {{ $reservation->num_siege }}</span></dd>
                <dt>Type de voyage</dt><dd>{{ $reservation->typeVoyage?->type_voyage ?? '—' }}</dd>
                <dt>Autocar</dt><dd class="sa-mono">{{ $reservation->autocar?->matricule ?? '—' }}</dd>
                <dt>Société</dt><dd>{{ $reservation->autocar?->societe?->raison_social ?? '—' }}</dd>
            </dl>
        </x-admin.card>
    </div>

    <div class="col-lg-4 sa-gap align-content-start">
        <x-admin.card title="Client" icon="bi-person">
            @if ($reservation->user)
                <div class="sa-person mb-3">
                    <span class="sa-avatar">{{ mb_substr($reservation->user->name, 0, 2) }}</span>
                    <div>
                        <div class="sa-strong">{{ $reservation->user->name }}</div>
                        <div class="sa-sub">{{ $reservation->user->telephone ?: 'Téléphone non renseigné' }}</div>
                    </div>
                </div>
                <a href="mailto:{{ $reservation->user->email }}" class="d-block small mb-3"><i class="bi bi-envelope me-1"></i> {{ $reservation->user->email }}</a>
                <a href="{{ route('reservation.admin.index', ['q' => $reservation->user->email]) }}" class="btn btn-soft btn-sm w-100">
                    <i class="bi bi-list-ul"></i> Toutes ses réservations
                </a>
            @else
                <span class="text-body-secondary">Compte supprimé</span>
            @endif
        </x-admin.card>

        <x-admin.card title="Paiement" icon="bi-cash-stack" tone="success">
            <dl class="sa-dl">
                <dt>Prix</dt><dd class="text-end sa-num">{{ number_format($reservation->prix, 2, ',', ' ') }} DH</dd>
                <dt>Frais</dt><dd class="text-end sa-num">{{ number_format($reservation->frais, 2, ',', ' ') }} DH</dd>
                <dt class="sa-strong">Total</dt><dd class="text-end sa-num fw-bold fs-5">{{ number_format($reservation->total(), 2, ',', ' ') }} DH</dd>
            </dl>
            <div class="sa-sub mt-2">Mode : {{ $reservation->modeReglement?->mode_reglement ?? '—' }}</div>
            <div class="sa-divider"></div>
            <dl class="sa-dl">
                <dt>Payée le</dt><dd class="text-end">{{ $reservation->paye_le?->format('d/m/Y H:i') ?? 'Non payée' }}</dd>
                @if ($reservation->paiement_ref)
                    <dt>Référence</dt><dd class="text-end sa-mono">{{ $reservation->paiement_ref }}</dd>
                @endif
                @if ($reservation->isCancelled())
                    <dt>Annulée le</dt><dd class="text-end">{{ $reservation->annulee_le?->format('d/m/Y H:i') }} <span class="sa-sub">({{ ['client' => 'par le client', 'admin' => 'par l\'équipe', 'systeme' => 'paiement non abouti'][$reservation->annulee_par] ?? '' }})</span></dd>
                    <dt>Remboursée le</dt><dd class="text-end">{{ $reservation->rembourse_le?->format('d/m/Y H:i') ?? ($reservation->needsRefund() ? 'À rembourser' : 'Rien à rembourser') }}</dd>
                    @if ($reservation->isPaid())
                        <dt>Montant remboursé</dt><dd class="text-end fw-semibold">{{ number_format($reservation->amountToRefund(), 2, ',', ' ') }} DH</dd>
                        <dt>Frais d'annulation</dt><dd class="text-end">{{ number_format((float) $reservation->frais_annulation, 2, ',', ' ') }} DH</dd>
                    @endif
                @endif
            </dl>
            @if ($reservation->encaissements->isNotEmpty())
                <div class="sa-divider"></div>
                <div class="sa-sub mb-1">Encaissé par l'équipe</div>
                <dl class="sa-dl">
                    @foreach ($reservation->encaissements as $e)
                        <dt>{{ $e->created_at->format('d/m H:i') }} · {{ $e->user?->name ?? '—' }}</dt>
                        <dd class="text-end sa-num">{{ number_format($e->montant, 2, ',', ' ') }} DH <span class="sa-sub">{{ \App\Models\Encaissement::MODES[$e->mode] ?? $e->mode }}</span></dd>
                    @endforeach
                </dl>
            @endif
        </x-admin.card>

        {{-- bus door: first scan, boarding, and every scan (a ticket shown twice or on the wrong bus shows up here) --}}
        <x-admin.card title="Contrôle" icon="bi-qr-code-scan" class="mt-3">
            <dl class="sa-dl">
                <dt>Premier scan</dt><dd class="text-end">{{ $reservation->scanne_le?->format('d/m/Y H:i') ?? 'Jamais scanné' }}</dd>
                <dt>Montée</dt><dd class="text-end">{{ $reservation->embarque_le ? $reservation->embarque_le->format('d/m/Y H:i') . ' · ' . ($reservation->embarquePar?->name ?? '—') : 'Pas encore' }}</dd>
            </dl>
            @if ($reservation->scans->isNotEmpty())
                <div class="sa-divider"></div>
                <dl class="sa-dl">
                    @foreach ($reservation->scans->take(10) as $scan)
                        <dt>{{ $scan->created_at->format('d/m H:i') }} · {{ $scan->user?->name ?? '—' }}</dt>
                        <dd class="text-end">{{ \App\Support\Controle::LIBELLES[$scan->resultat] ?? $scan->resultat }}</dd>
                    @endforeach
                </dl>
            @endif
        </x-admin.card>
    </div>
</div>
@endsection

@extends('admin.Layout.app')
@section('title', $user->name)

@section('content')
@php
    $me = auth()->user();
    $canUpdate = $me->hasPermission('clients.update');
@endphp

<x-admin.page-header :title="$user->name" :subtitle="$user->email" :back="route('admin.clients.index')" backLabel="Clients">
    <span class="sa-chip {{ $user->email_verified_at ? 'success' : 'warning' }} dot align-self-center">
        {{ $user->email_verified_at ? 'Email vérifié' : 'Email non vérifié' }}
    </span>
    @if ($me->isSuperAdmin())
        <a href="{{ route('admin.users.edit', ['user' => $user->id, 'acces' => 1]) }}" class="btn btn-soft" title="Transformer ce client en compte de l'équipe ou de compagnie">
            <i class="bi bi-shield-lock"></i> Donner un accès admin
        </a>
    @endif
</x-admin.page-header>

<div class="row g-3 mb-4">
    <div class="col-xl col-sm-4 col-6"><x-admin.stat label="Billets" :value="$stats['billets']" icon="bi-ticket-perforated" /></div>
    <div class="col-xl col-sm-4 col-6"><x-admin.stat label="Payés" :value="$stats['payes']" icon="bi-check2-circle" tone="success" /></div>
    <div class="col-xl col-sm-4 col-6"><x-admin.stat label="À payer (à venir)" :value="$stats['non_payes']" icon="bi-hourglass-split" tone="warning" /></div>
    <div class="col-xl col-sm-6 col-6"><x-admin.stat label="Annulés" :value="$stats['annules']" icon="bi-x-circle" /></div>
    <div class="col-xl col-sm-6 col-12">
        <x-admin.stat label="Absences (non payé, pas monté)" :value="$stats['absences'] . ' / ' . config('safar.absences_max')" icon="bi-person-x" :tone="$stats['absences'] >= config('safar.absences_max') ? 'danger' : null">
            @if ($stats['absences'] >= config('safar.absences_max'))
                Paiement par carte obligatoire
            @endif
        </x-admin.stat>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="sa-gap">
            <x-admin.card title="Identité" icon="bi-person">
                @if ($canUpdate)
                    <form action="{{ route('admin.clients.update', $user) }}" method="POST" class="row g-3">
                        @csrf
                        @method('PUT')
                        <x-admin.field name="name" label="Nom complet" col="col-12" required :value="$user->name" />
                        <x-admin.field type="email" name="email" label="Email" col="col-12" required icon="bi-envelope" :value="$user->email" />
                        <x-admin.field name="telephone" label="Téléphone" col="col-12" icon="bi-telephone" :value="$user->telephone" />
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <span class="sa-sub">Inscrit le {{ $user->created_at?->format('d/m/Y') }}</span>
                            <button class="btn btn-primary"><i class="bi bi-check2"></i> Enregistrer</button>
                        </div>
                    </form>
                @else
                    <dl class="sa-dl">
                        <dt>Nom</dt><dd>{{ $user->name }}</dd>
                        <dt>Email</dt><dd>{{ $user->email }}</dd>
                        <dt>Téléphone</dt><dd>{{ $user->telephone ?: '—' }}</dd>
                        <dt>Inscrit le</dt><dd>{{ $user->created_at?->format('d/m/Y') }}</dd>
                    </dl>
                @endif
            </x-admin.card>

            @if ($canUpdate)
                <x-admin.card title="Mot de passe" subtitle="Le nouveau mot de passe remplace l'actuel immédiatement." icon="bi-key">
                    <form action="{{ route('admin.clients.update-password', $user) }}" method="POST" class="row g-3">
                        @csrf
                        @method('PUT')
                        <x-admin.field type="password" name="password" label="Nouveau mot de passe" col="col-md-6" required autocomplete="new-password" />
                        <x-admin.field type="password" name="password_confirmation" label="Confirmation" col="col-md-6" required autocomplete="new-password" />
                        <div class="col-12 text-end">
                            <button class="btn btn-soft"><i class="bi bi-key"></i> Changer le mot de passe</button>
                        </div>
                    </form>
                </x-admin.card>
            @endif
        </div>
    </div>

    <div class="col-xl-7">
        <x-admin.card title="Réservations" icon="bi-ticket-perforated" flush>
            @if ($reservations->isEmpty())
                <x-admin.empty icon="bi-inbox" title="Aucune réservation" />
            @else
                <div class="sa-table-wrap">
                    <table class="table sa-table align-middle">
                        <thead><tr><th>N°</th><th>Trajet</th><th>Départ</th><th>Siège</th><th>État</th><th class="text-end">Total</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($reservations as $r)
                                <tr>
                                    <td class="sa-num sa-strong">#{{ $r->id }}</td>
                                    <td><span class="sa-route">{{ $r->villeDepart?->ville }} <i class="bi bi-arrow-right"></i> {{ $r->villeArrivee?->ville }}</span></td>
                                    <td class="sa-num">{{ \Carbon\Carbon::parse($r->date_depart)->format('d/m/Y') }} <span class="sa-sub">{{ \Carbon\Carbon::parse($r->heure_depart)->format('H:i') }}</span></td>
                                    <td><span class="sa-chip brand">N° {{ $r->num_siege }}</span></td>
                                    <td>@php [$badge, $tone] = $r->statusBadge(); @endphp<span class="sa-chip {{ $tone }} dot">{{ $badge }}</span></td>
                                    <td class="text-end sa-num text-nowrap">{{ number_format($r->total(), 2, ',', ' ') }} DH</td>
                                    <td class="text-end">
                                        @if ($me->hasPermission('reservations.read'))
                                            <x-admin.row-actions :show="route('reservation.admin.show', $r->id)" />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-admin.table-footer :paginator="$reservations" label="réservation(s)" />
            @endif
        </x-admin.card>
    </div>
</div>
@endsection

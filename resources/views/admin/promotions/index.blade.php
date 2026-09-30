@extends('admin.Layout.app')
@section('title', 'Promotions')

@section('content')
@php
    $user = auth()->user();
    $canUpdate = $user->hasPermission('promotions.update');
    $canDelete = $user->hasPermission('promotions.delete');
@endphp

<x-admin.page-header title="Promotions" subtitle="Codes promo à taper sur la page de paiement : pourcentage ou montant sur toute la commande.">
    @if ($user->hasPermission('promotions.create'))
        <a href="{{ route('promotions.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nouveau code</a>
    @endif
</x-admin.page-header>

<x-admin.card>
    @if ($promotions->isEmpty())
        <x-admin.empty icon="bi-ticket-perforated" title="Aucun code promo" text="Créez un code (ex : ETE2026, -10 %) pour attirer des clients." />
    @else
        <div class="table-responsive">
            <table class="table sa-table align-middle mb-0">
                <thead>
                    <tr><th>Code</th><th>Réduction</th><th>Période</th><th>Utilisations</th><th class="text-end">Remises accordées</th><th>État</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($promotions as $p)
                        @php
                            $expire = $p->fin && today()->gt($p->fin);
                            $epuise = $p->max_utilisations !== null && $p->utilisations >= $p->max_utilisations;
                        @endphp
                        <tr>
                            <td><span class="sa-mono sa-strong">{{ $p->code }}</span></td>
                            <td class="sa-strong">{{ $p->libelle() }} @if ($p->min_montant)<div class="sa-sub">dès {{ number_format($p->min_montant, 0, ',', ' ') }} DH</div>@endif</td>
                            <td class="sa-sub">{{ $p->debut?->format('d/m/Y') ?? '—' }} → {{ $p->fin?->format('d/m/Y') ?? '∞' }}</td>
                            <td class="sa-num">{{ $p->utilisations }}{{ $p->max_utilisations ? ' / ' . $p->max_utilisations : '' }}</td>
                            <td class="text-end sa-num">{{ number_format((float) $p->reservations_sum_remise, 2, ',', ' ') }} DH</td>
                            <td>
                                @if (! $p->actif)<span class="sa-chip muted dot">Désactivé</span>
                                @elseif ($expire)<span class="sa-chip muted dot">Expiré</span>
                                @elseif ($epuise)<span class="sa-chip warning dot">Épuisé</span>
                                @else<span class="sa-chip success dot">Actif</span>@endif
                            </td>
                            <td class="text-end">
                                <x-admin.row-actions :edit="$canUpdate ? route('promotions.edit', $p) : null" :delete="$canDelete ? route('promotions.destroy', $p) : null"
                                                     :confirm="'Supprimer le code ' . $p->code . ' ?'" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.table-footer :paginator="$promotions" label="code(s)" />
    @endif
</x-admin.card>
@endsection

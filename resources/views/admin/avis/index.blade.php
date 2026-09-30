@extends('admin.Layout.app')
@section('title', 'Avis')

@section('content')
@php
    $user = auth()->user();
    $canUpdate = $user->hasPermission('avis.update');
    $canDelete = $user->hasPermission('avis.delete');
    $f = fn ($key) => $filters[$key] ?? '';
@endphp

<x-admin.page-header title="Avis des voyageurs" subtitle="Notes laissées après le trajet. Un avis masqué ne compte plus dans la note de la compagnie.">
    <span class="sa-chip warning align-self-center"><i class="bi bi-star-fill"></i> {{ number_format($moyenne, 1, ',', '') }} / 5</span>
</x-admin.page-header>

<x-admin.filters :action="route('avis.index')">
    @if ($societes->isNotEmpty())
        <div class="col-lg-3 col-md-4 col-6">
            <label class="form-label">Compagnie</label>
            <select name="societe_id" class="form-select">
                <option value="">Toutes</option>
                @foreach ($societes as $s)
                    <option value="{{ $s->id }}" @selected($f('societe_id') == $s->id)>{{ $s->raison_social }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="col-lg-2 col-md-4 col-6">
        <label class="form-label">Note</label>
        <select name="note" class="form-select">
            <option value="">Toutes</option>
            @for ($n = 5; $n >= 1; $n--)
                <option value="{{ $n }}" @selected($f('note') == $n)>{{ str_repeat('★', $n) }}</option>
            @endfor
        </select>
    </div>
</x-admin.filters>

<x-admin.card>
    @if ($avis->isEmpty())
        <x-admin.empty icon="bi-star" title="Aucun avis" text="Les voyageurs reçoivent une demande d'avis le lendemain de leur trajet." />
    @else
        <div class="table-responsive">
            <table class="table sa-table align-middle mb-0">
                <thead><tr><th>Note</th><th>Commentaire</th><th>Compagnie</th><th>Voyageur · trajet</th><th>État</th><th></th></tr></thead>
                <tbody>
                    @foreach ($avis as $a)
                        <tr class="{{ $a->publie ? '' : 'opacity-50' }}">
                            <td class="text-nowrap" style="color:#f59e0b;">{{ str_repeat('★', $a->note) }}<span style="color:#cbd5e1;">{{ str_repeat('★', 5 - $a->note) }}</span></td>
                            <td style="max-width: 360px;">{{ $a->commentaire ?: '—' }}<div class="sa-sub">{{ $a->created_at->format('d/m/Y') }}</div></td>
                            <td>{{ $a->societe?->raison_social }}</td>
                            <td>
                                <div class="sa-strong">{{ $a->user?->name }}</div>
                                <div class="sa-sub">{{ $a->reservation?->villeDepart?->ville }} → {{ $a->reservation?->villeArrivee?->ville }}</div>
                            </td>
                            <td>@if ($a->publie)<span class="sa-chip success dot">Visible</span>@else<span class="sa-chip muted dot">Masqué</span>@endif</td>
                            <td class="text-end text-nowrap">
                                <x-admin.row-actions :delete="$canDelete ? route('avis.destroy', $a) : null" confirm="Supprimer cet avis ?">
                                    @if ($canUpdate)
                                        <form action="{{ route('avis.publier', $a) }}" method="POST" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button class="sa-icon-btn" title="{{ $a->publie ? 'Masquer' : 'Afficher' }}"><i class="bi {{ $a->publie ? 'bi-eye-slash' : 'bi-eye' }}"></i></button>
                                        </form>
                                    @endif
                                </x-admin.row-actions>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.table-footer :paginator="$avis" label="avis" />
    @endif
</x-admin.card>
@endsection

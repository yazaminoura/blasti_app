@extends('admin.Layout.app')
@section('title', 'Sociétés')

@section('content')
@php
    $user = auth()->user();
    $canUpdate = $user->hasPermission('societes.update');
    $canDelete = $user->hasPermission('societes.delete');
@endphp

<x-admin.page-header title="Sociétés" subtitle="Les sociétés de transport partenaires et leurs contacts.">
    @if ($user->hasPermission('societes.create'))
        <a href="{{ route('societes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nouvelle société</a>
    @endif
</x-admin.page-header>

<x-admin.card flush>
    @if ($societes->isEmpty())
        <x-admin.empty icon="bi-buildings" title="Aucune société" text="Ajoutez une société pour pouvoir lui rattacher des autocars." />
    @else
        <div class="sa-table-wrap">
            <table class="table sa-table align-middle">
                <thead>
                    <tr>
                        <th>Société</th>
                        <th>Contact</th>
                        <th>Ville</th>
                        <th>ICE</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($societes as $societe)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($societe->logo)
                                        <img src="{{ asset('storage/' . $societe->logo) }}" alt="" class="sa-thumb round">
                                    @else
                                        <span class="sa-thumb-empty round"><i class="bi bi-buildings"></i></span>
                                    @endif
                                    <span class="sa-strong">{{ $societe->raison_social }}</span>
                                </div>
                            </td>
                            <td>
                                <div>{{ $societe->nom_contact }}</div>
                                <div class="sa-sub">
                                    @if ($societe->email)<a href="mailto:{{ $societe->email }}">{{ $societe->email }}</a>@endif
                                    @if ($societe->tel) · {{ $societe->tel }}@endif
                                </div>
                            </td>
                            <td>
                                <div>{{ $societe->ville }}</div>
                                <div class="sa-sub text-truncate" style="max-width: 200px;">{{ $societe->adresse }}</div>
                            </td>
                            <td><span class="sa-mono">{{ $societe->ice }}</span></td>
                            <td class="text-end">
                                <x-admin.row-actions
                                    :edit="$canUpdate ? route('societes.edit', $societe->id) : null"
                                    :delete="$canDelete ? route('societes.destroy', $societe->id) : null"
                                    :confirm="'Supprimer la société ' . $societe->raison_social . ' ?'" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.table-footer :paginator="$societes" label="société(s)" />
    @endif
</x-admin.card>
@endsection

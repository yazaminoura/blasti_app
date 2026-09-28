@extends('admin.Layout.app')
@section('title', 'Autocars')

@section('content')
@php
    $user = auth()->user();
    $canUpdate = $user->hasPermission('autocars.update');
    $canDelete = $user->hasPermission('autocars.delete');
@endphp

<x-admin.page-header title="Autocars" subtitle="La flotte de véhicules et leur capacité.">
    <a href="{{ route('admin.export.autocars') }}" class="btn btn-soft"><i class="bi bi-download"></i> Exporter</a>
    @if ($user->hasPermission('autocars.create'))
        <a href="{{ route('autocars.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nouvel autocar</a>
    @endif
</x-admin.page-header>

<x-admin.card flush>
    @if ($autocars->isEmpty())
        <x-admin.empty icon="bi-bus-front" title="Aucun autocar" text="Ajoutez un premier véhicule pour pouvoir programmer des voyages." />
    @else
        <div class="sa-table-wrap">
            <table class="table sa-table align-middle">
                <thead>
                    <tr>
                        <th>Autocar</th>
                        <th>Société</th>
                        <th>Capacité</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($autocars as $autocar)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($autocar->image)
                                        <img src="{{ asset('storage/' . $autocar->image) }}" alt="" class="sa-thumb">
                                    @else
                                        <span class="sa-thumb-empty"><i class="bi bi-bus-front"></i></span>
                                    @endif
                                    <div>
                                        <div class="sa-mono sa-strong">{{ $autocar->matricule }}</div>
                                        <div class="sa-sub">N° {{ $autocar->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $autocar->societe?->raison_social ?? '—' }}</td>
                            <td><span class="sa-chip info"><i class="bi bi-people"></i> {{ $autocar->nbr_siege }} sièges</span></td>
                            <td class="text-end">
                                <x-admin.row-actions
                                    :edit="$canUpdate ? route('autocars.edit', $autocar->id) : null"
                                    :delete="$canDelete ? route('autocars.destroy', $autocar->id) : null"
                                    :confirm="'Supprimer l\'autocar ' . $autocar->matricule . ' ?'" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.table-footer :paginator="$autocars" label="autocar(s)" />
    @endif
</x-admin.card>
@endsection

@extends('admin.Layout.app')
@section('title', 'Clients')

@section('content')
<x-admin.page-header title="Clients" subtitle="Les comptes clients inscrits sur le site.">
    <a href="{{ route('admin.export.users') }}" class="btn btn-soft"><i class="bi bi-download"></i> Exporter</a>
    @if (auth()->user()->hasPermission('utilisateurs.create'))
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nouveau client</a>
    @endif
</x-admin.page-header>

<x-admin.card flush>
    <x-slot:actions>
        <form method="GET" action="{{ route('admin.clients.index') }}" class="sa-search">
            <i class="bi bi-search"></i>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Nom, email ou téléphone" style="min-width: 260px;">
        </form>
    </x-slot:actions>
    @include('admin.users._table', ['users' => $users, 'showReservations' => true])
</x-admin.card>
@endsection

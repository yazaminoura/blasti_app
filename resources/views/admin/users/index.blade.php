@extends('admin.Layout.app')
@section('title', 'Utilisateurs & rôles')

@section('content')
@php
    $superAdmin = auth()->user()->isSuperAdmin();
    $tabs = [
        null => ['Tous', 'bi-people'],
        'super' => ['Super admins', 'bi-shield-check'],
        'equipe' => ['Équipe', 'bi-person-badge'],
        'compagnie' => ['Compagnies', 'bi-buildings'],
    ];
@endphp

<x-admin.page-header title="Utilisateurs & rôles" subtitle="Les comptes qui ont accès à l'administration. Les voyageurs sont dans « Clients ».">
    @if (auth()->user()->hasPermission('utilisateurs.create'))
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nouvel utilisateur</a>
    @endif
</x-admin.page-header>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <nav class="sa-tabs" aria-label="Type de compte">
        @foreach ($tabs as $key => [$label, $icon])
            <a href="{{ route('admin.users.index', array_filter(['type' => $key, 'q' => request('q')])) }}"
               class="sa-tab {{ $type === ($key ?: null) ? 'active' : '' }}">
                <i class="bi {{ $icon }}"></i> {{ $label }}
                <span class="sa-tab-count">{{ $counts[$key ?: 'tous'] }}</span>
            </a>
        @endforeach
    </nav>
    <form method="GET" action="{{ route('admin.users.index') }}" class="sa-search">
        @if ($type)<input type="hidden" name="type" value="{{ $type }}">@endif
        <i class="bi bi-search"></i>
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Nom, email ou téléphone" style="min-width: 260px;">
    </form>
</div>

<div class="row g-4">
    <div class="col-xl-8 order-xl-1 order-2">
        <x-admin.card flush>
            @include('admin.users._table', ['users' => $users, 'team' => true])
        </x-admin.card>
    </div>

    <div class="col-xl-4 order-xl-2 order-1">
        <x-admin.card title="Rôles" subtitle="Chaque rôle donne accès à certaines pages." icon="bi-shield-lock" flush>
            @if ($superAdmin)
                <x-slot:actions>
                    <a href="{{ route('admin.roles.create') }}" class="sa-icon-btn" title="Nouveau rôle"><i class="bi bi-plus-lg"></i></a>
                </x-slot:actions>
            @endif

            <div class="p-2 d-grid gap-1">
                <a href="{{ route('admin.users.index', ['type' => 'super']) }}" class="d-flex align-items-center gap-2 px-2 py-2 text-reset text-decoration-none" data-sa-row>
                    <i class="bi bi-shield-check text-primary"></i>
                    <div class="flex-grow-1">
                        <div class="sa-strong">Super administrateur</div>
                        <div class="sa-sub">Accès complet · {{ $counts['super'] }} compte(s)</div>
                    </div>
                </a>
                @forelse ($roles as $role)
                    <div class="d-flex align-items-center justify-content-between gap-2 px-2 py-2" data-sa-row>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-badge sa-cell-muted"></i>
                            <div>
                                <div class="sa-strong">{{ $role->name }}</div>
                                <div class="sa-sub">{{ $role->users_count }} compte(s)</div>
                            </div>
                        </div>
                        @if ($superAdmin)
                            <x-admin.row-actions :edit="route('admin.roles.edit', $role->id)" :delete="route('admin.roles.destroy', $role->id)"
                                                 :confirm="'Supprimer le rôle « ' . $role->name . ' » ?'" />
                        @endif
                    </div>
                @empty
                    <div class="px-2 py-3 sa-sub">Aucun rôle défini.</div>
                @endforelse
            </div>
        </x-admin.card>
    </div>
</div>
@endsection

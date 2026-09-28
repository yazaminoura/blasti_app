@extends('admin.Layout.app')
@section('title', 'Utilisateurs & rôles')

@section('content')
@php $superAdmin = auth()->user()->isSuperAdmin(); @endphp

<x-admin.page-header title="Utilisateurs & rôles" subtitle="Les comptes et les droits d'accès de l'équipe.">
    @if (auth()->user()->hasPermission('utilisateurs.create'))
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nouvel utilisateur</a>
    @endif
</x-admin.page-header>

<div class="row g-4">
    <div class="col-xl-4">
        <x-admin.card title="Rôles" subtitle="Chaque rôle donne accès à certaines pages." icon="bi-shield-lock" flush>
            @if ($superAdmin)
                <x-slot:actions>
                    <a href="{{ route('admin.roles.create') }}" class="sa-icon-btn" title="Nouveau rôle"><i class="bi bi-plus-lg"></i></a>
                </x-slot:actions>
            @endif

            <div class="p-2">
                <div class="d-flex align-items-center justify-content-between gap-2 px-2 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-primary"></i>
                        <div>
                            <div class="sa-strong">Super administrateur</div>
                            <div class="sa-sub">Administrateur sans rôle, accès complet</div>
                        </div>
                    </div>
                </div>
                @forelse ($roles as $role)
                    <div class="d-flex align-items-center justify-content-between gap-2 px-2 py-2 border-top">
                        <div>
                            <div class="sa-strong">{{ $role->name }}</div>
                            <div class="sa-sub">{{ $role->users_count }} utilisateur(s)</div>
                        </div>
                        @if ($superAdmin)
                            <x-admin.row-actions :edit="route('admin.roles.edit', $role->id)" :delete="route('admin.roles.destroy', $role->id)"
                                                 :confirm="'Supprimer le rôle « ' . $role->name . ' » ?'" />
                        @endif
                    </div>
                @empty
                    <div class="px-2 py-3 border-top sa-sub">Aucun rôle défini. Sans rôle, un administrateur a un accès complet.</div>
                @endforelse
            </div>
        </x-admin.card>
    </div>

    <div class="col-xl-8">
        <x-admin.card title="Comptes" icon="bi-people" flush>
            <x-slot:actions>
                <form method="GET" action="{{ route('admin.users.index') }}" class="sa-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Nom, email ou téléphone" style="min-width: 240px;">
                </form>
            </x-slot:actions>
            @include('admin.users._table', ['users' => $users])
        </x-admin.card>
    </div>
</div>
@endsection

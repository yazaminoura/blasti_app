@extends('admin.Layout.app')
@section('title', 'Modifier l\'utilisateur')

@section('content')
<x-admin.page-header :title="$user->name" :subtitle="$user->email" :back="$user->isadmin ? route('admin.users.index') : route('admin.clients.index')"
                     :backLabel="$user->isadmin ? 'Utilisateurs & rôles' : 'Clients'" />

<div class="sa-gap">
    <x-admin.form :action="route('admin.users.update', $user->id)" method="PUT" :cancel="$user->isadmin ? route('admin.users.index') : route('admin.clients.index')">
        <x-admin.form-section title="Identité" icon="bi-person">
            <x-admin.field name="name" label="Nom complet" col="col-md-6" required :value="$user->name" />
            <x-admin.field type="email" name="email" label="Email" col="col-md-6" required icon="bi-envelope" :value="$user->email" />
        </x-admin.form-section>

        @if ($canManageAccess)
            <x-admin.form-section title="Accès à l'administration" description="Administrateur sans rôle = super administrateur (accès complet). Avec un rôle = accès limité aux permissions du rôle." icon="bi-shield-lock">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="isadmin" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="isadmin" id="isadmin" value="1" @checked(old('isadmin', $user->isadmin))>
                        <label class="form-check-label fw-semibold" for="isadmin">Peut accéder à l'administration</label>
                    </div>
                </div>
                <x-admin.field type="select" name="role" label="Rôle" col="col-md-6" empty="Aucun rôle"
                               :options="$roles->pluck('name', 'id')" :value="$user->roles->first()?->id" />
            </x-admin.form-section>
        @endif
    </x-admin.form>

    <x-admin.form :action="route('admin.users.update-password', $user->id)" method="PUT" submit="Changer le mot de passe" submitIcon="bi-key"
                  hint="Le nouveau mot de passe remplace l'actuel immédiatement.">
        <x-admin.form-section title="Mot de passe" description="8 caractères minimum." icon="bi-key">
            <x-admin.field type="password" name="password" label="Nouveau mot de passe" col="col-md-6" required autocomplete="new-password" />
            <x-admin.field type="password" name="password_confirmation" label="Confirmation" col="col-md-6" required autocomplete="new-password" />
        </x-admin.form-section>
    </x-admin.form>
</div>
@endsection

@extends('admin.Layout.app')
@section('title', 'Nouvel utilisateur')

@section('content')
<x-admin.page-header title="Nouvel utilisateur" subtitle="Créez un compte client ou un compte pour un membre de l'équipe." :back="route('admin.users.index')" backLabel="Utilisateurs & rôles" />

<x-admin.form :action="route('admin.users.store')" :cancel="route('admin.users.index')" submit="Créer le compte" submitIcon="bi-person-plus">
    <x-admin.form-section title="Identité" icon="bi-person">
        <x-admin.field name="name" label="Nom complet" col="col-md-6" required autofocus placeholder="Ex : Salma Bennani" />
        <x-admin.field type="email" name="email" label="Email" col="col-md-6" required icon="bi-envelope" placeholder="email@exemple.com" />
    </x-admin.form-section>

    <x-admin.form-section title="Mot de passe" description="8 caractères minimum. La personne pourra le changer depuis son profil." icon="bi-key">
        <x-admin.field type="password" name="password" label="Mot de passe" col="col-md-6" required autocomplete="new-password" />
        <x-admin.field type="password" name="password_confirmation" label="Confirmation" col="col-md-6" required autocomplete="new-password" />
    </x-admin.form-section>

    @if (auth()->user()->isSuperAdmin())
        <x-admin.form-section title="Accès à l'administration" description="Administrateur sans rôle = super administrateur (accès complet). Avec un rôle = accès limité aux permissions du rôle." icon="bi-shield-lock">
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="isadmin" id="isadmin" value="1" @checked(old('isadmin'))>
                    <label class="form-check-label fw-semibold" for="isadmin">Peut accéder à l'administration</label>
                </div>
            </div>
            <x-admin.field type="select" name="role" label="Rôle" col="col-md-6" empty="Aucun rôle"
                           :options="$roles->pluck('name', 'id')" hint="Laissez « Aucun rôle » pour un client." />
            <x-admin.field type="select" name="societe_id" label="Compagnie (espace compagnie)" col="col-md-6" empty="Toutes (équipe BLASTI)"
                           :options="$societes->pluck('raison_social', 'id')" hint="Un compte lié à une compagnie ne voit que ses autocars, voyages, billets et avis." />
        </x-admin.form-section>
    @endif
</x-admin.form>
@endsection

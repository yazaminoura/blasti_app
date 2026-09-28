@extends('admin.Layout.app')
@section('title', 'Nouveau rôle')

@section('content')
<x-admin.page-header title="Nouveau rôle" subtitle="Un rôle regroupe les pages et actions autorisées pour un membre de l'équipe." :back="route('admin.users.index')" backLabel="Utilisateurs & rôles" />

<x-admin.form :action="route('admin.roles.store')" :cancel="route('admin.users.index')" submit="Créer le rôle" submitIcon="bi-plus-lg" style="max-width: none;">
    <x-admin.form-section title="Rôle" icon="bi-shield-lock">
        <x-admin.field name="name" label="Nom du rôle" col="col-md-6" required autofocus placeholder="Ex : Agent de réservation" />
    </x-admin.form-section>

    @include('admin.roles._matrix', ['checked' => []])
</x-admin.form>
@endsection

@extends('admin.Layout.app')
@section('title', 'Nouvelle ville')

@section('content')
<x-admin.page-header title="Nouvelle ville" subtitle="Elle sera proposée comme ville de départ et d'arrivée." :back="route('villes.index')" backLabel="Villes" />

<x-admin.form :action="route('villes.store')" files :cancel="route('villes.index')" submit="Ajouter la ville" submitIcon="bi-plus-lg">
    <x-admin.form-section title="Ville" icon="bi-geo-alt">
        <x-admin.field name="ville" label="Nom de la ville" col="col-md-8" required autofocus placeholder="Ex : Marrakech" />
    </x-admin.form-section>
    <x-admin.form-section title="Photo" description="Affichée sur la page d'accueil (Nos destinations). Format paysage conseillé." icon="bi-image">
        <x-admin.upload name="image" label="Photo de la ville" hint="JPG, PNG ou WEBP, 3 Mo maximum." />
    </x-admin.form-section>
</x-admin.form>
@endsection

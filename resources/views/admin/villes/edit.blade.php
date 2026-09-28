@extends('admin.Layout.app')
@section('title', 'Modifier la ville')

@section('content')
<x-admin.page-header :title="$ville->ville" subtitle="Renommer la ville met à jour tous les voyages qui l'utilisent." :back="route('villes.index')" backLabel="Villes" />

<x-admin.form :action="route('villes.update', $ville)" method="PUT" files :cancel="route('villes.index')">
    <x-admin.form-section title="Ville" icon="bi-geo-alt">
        <x-admin.field name="ville" label="Nom de la ville" col="col-md-8" required :value="$ville->ville" />
    </x-admin.form-section>
    <x-admin.form-section title="Photo" description="Affichée sur la page d'accueil (Nos destinations). Format paysage conseillé." icon="bi-image">
        <x-admin.upload name="image" label="Photo de la ville" :current="$ville->image" hint="JPG, PNG ou WEBP, 3 Mo maximum." />
    </x-admin.form-section>
</x-admin.form>
@endsection

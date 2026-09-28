@extends('admin.Layout.app')
@section('title', 'Modifier l\'équipement')

@section('content')
<x-admin.page-header :title="$equipement->equipement" subtitle="Modifier l'équipement." :back="route('equipements.index')" backLabel="Équipements" />

<x-admin.form :action="route('equipements.update', $equipement->id)" method="PUT" :cancel="route('equipements.index')">
    <x-admin.form-section title="Équipement" icon="bi-tools">
        <x-admin.field name="equipement" label="Nom de l'équipement" col="col-md-8" required :value="$equipement->equipement" />
    </x-admin.form-section>
</x-admin.form>
@endsection

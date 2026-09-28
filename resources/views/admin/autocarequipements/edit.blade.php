@extends('admin.Layout.app')
@section('title', 'Modifier l\'association')

@section('content')
<x-admin.page-header title="Modifier l'équipement d'un autocar" :back="route('autocarequipements.index')" backLabel="Équipements par autocar" />

<x-admin.form :action="route('autocarequipements.update', $autocarequipement->id)" method="PUT" :cancel="route('autocarequipements.index')">
    <x-admin.form-section title="Association" icon="bi-link-45deg">
        <x-admin.field type="select" name="autocar_id" label="Autocar" col="col-md-6" required empty="Choisir un autocar"
                       :options="$autocars->pluck('matricule', 'id')" :value="$autocarequipement->autocar_id" />
        <x-admin.field type="select" name="equipement_id" label="Équipement" col="col-md-6" required empty="Choisir un équipement"
                       :options="$equipements->pluck('equipement', 'id')" :value="$autocarequipement->equipement_id" />
    </x-admin.form-section>
</x-admin.form>
@endsection

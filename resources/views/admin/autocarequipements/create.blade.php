@extends('admin.Layout.app')
@section('title', 'Associer un équipement')

@section('content')
<x-admin.page-header title="Associer un équipement à un autocar" subtitle="L'équipement apparaîtra sur les voyages de cet autocar." :back="route('autocarequipements.index')" backLabel="Équipements par autocar" />

<x-admin.form :action="route('autocarequipements.store')" :cancel="route('autocarequipements.index')" submit="Associer" submitIcon="bi-link-45deg">
    <x-admin.form-section title="Association" icon="bi-link-45deg">
        <x-admin.field type="select" name="autocar_id" label="Autocar" col="col-md-6" required empty="Choisir un autocar"
                       :options="$autocars->pluck('matricule', 'id')" />
        <x-admin.field type="select" name="equipement_id" label="Équipement" col="col-md-6" required empty="Choisir un équipement"
                       :options="$equipements->pluck('equipement', 'id')" />
    </x-admin.form-section>
</x-admin.form>
@endsection

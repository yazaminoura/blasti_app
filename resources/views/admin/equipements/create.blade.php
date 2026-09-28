@extends('admin.Layout.app')
@section('title', 'Nouvel équipement')

@section('content')
<x-admin.page-header title="Nouvel équipement" subtitle="Un équipement du véhicule, par exemple climatisation ou toilettes." :back="route('equipements.index')" backLabel="Équipements" />

<x-admin.form :action="route('equipements.store')" :cancel="route('equipements.index')" submit="Ajouter l'équipement" submitIcon="bi-plus-lg">
    <x-admin.form-section title="Équipement" description="Vous pourrez ensuite l'associer aux autocars." icon="bi-tools">
        <x-admin.field name="equipement" label="Nom de l'équipement" col="col-md-8" required autofocus placeholder="Ex : Climatisation" />
    </x-admin.form-section>
</x-admin.form>
@endsection

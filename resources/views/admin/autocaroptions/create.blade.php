@extends('admin.Layout.app')
@section('title', 'Associer une option')

@section('content')
<x-admin.page-header title="Associer une option à un autocar" subtitle="L'option apparaîtra sur les voyages de cet autocar." :back="route('autocaroptions.index')" backLabel="Options par autocar" />

<x-admin.form :action="route('autocaroptions.store')" :cancel="route('autocaroptions.index')" submit="Associer" submitIcon="bi-link-45deg">
    <x-admin.form-section title="Association" icon="bi-link-45deg">
        <x-admin.field type="select" name="autocar_id" label="Autocar" col="col-md-6" required empty="Choisir un autocar"
                       :options="$autocars->pluck('matricule', 'id')" />
        <x-admin.field type="select" name="option_id" label="Option" col="col-md-6" required empty="Choisir une option"
                       :options="$options->pluck('option', 'id')" />
    </x-admin.form-section>
</x-admin.form>
@endsection

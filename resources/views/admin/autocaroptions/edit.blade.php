@extends('admin.Layout.app')
@section('title', 'Modifier l\'association')

@section('content')
<x-admin.page-header title="Modifier l'option d'un autocar" :back="route('autocaroptions.index')" backLabel="Options par autocar" />

<x-admin.form :action="route('autocaroptions.update', $autocaroption->id)" method="PUT" :cancel="route('autocaroptions.index')">
    <x-admin.form-section title="Association" icon="bi-link-45deg">
        <x-admin.field type="select" name="autocar_id" label="Autocar" col="col-md-6" required empty="Choisir un autocar"
                       :options="$autocars->pluck('matricule', 'id')" :value="$autocaroption->autocar_id" />
        <x-admin.field type="select" name="option_id" label="Option" col="col-md-6" required empty="Choisir une option"
                       :options="$options->pluck('option', 'id')" :value="$autocaroption->option_id" />
    </x-admin.form-section>
</x-admin.form>
@endsection

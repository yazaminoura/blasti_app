@extends('admin.Layout.app')
@section('title', 'Modifier le type de voyage')

@section('content')
<x-admin.page-header :title="$type_voyage->type_voyage" subtitle="Modifier le type de voyage." :back="route('type_voyages.index')" backLabel="Types de voyage" />

<x-admin.form :action="route('type_voyages.update', $type_voyage->id)" method="PUT" :cancel="route('type_voyages.index')">
    <x-admin.form-section title="Type de voyage" icon="bi-tags">
        <x-admin.field name="type_voyage" label="Nom du type" col="col-md-8" required :value="$type_voyage->type_voyage" />
    </x-admin.form-section>
</x-admin.form>
@endsection

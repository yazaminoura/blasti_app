@extends('admin.Layout.app')
@section('title', 'Nouveau type de voyage')

@section('content')
<x-admin.page-header title="Nouveau type de voyage" subtitle="Par exemple : Confort, Premium, Express." :back="route('type_voyages.index')" backLabel="Types de voyage" />

<x-admin.form :action="route('type_voyages.store')" :cancel="route('type_voyages.index')" submit="Ajouter le type" submitIcon="bi-plus-lg">
    <x-admin.form-section title="Type de voyage" icon="bi-tags">
        <x-admin.field name="type_voyage" label="Nom du type" col="col-md-8" required autofocus placeholder="Ex : Premium" />
    </x-admin.form-section>
</x-admin.form>
@endsection

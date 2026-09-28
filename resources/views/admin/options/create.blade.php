@extends('admin.Layout.app')
@section('title', 'Nouvelle option')

@section('content')
<x-admin.page-header title="Nouvelle option" subtitle="Un service proposé à bord, par exemple Wi-Fi ou prise USB." :back="route('options.index')" backLabel="Options" />

<x-admin.form :action="route('options.store')" :cancel="route('options.index')" submit="Ajouter l'option" submitIcon="bi-plus-lg">
    <x-admin.form-section title="Option" description="Vous pourrez ensuite l'associer aux autocars." icon="bi-stars">
        <x-admin.field name="option" label="Nom de l'option" col="col-md-8" required autofocus placeholder="Ex : Wi-Fi" />
    </x-admin.form-section>
</x-admin.form>
@endsection

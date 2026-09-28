@extends('admin.Layout.app')
@section('title', 'Modifier l\'option')

@section('content')
<x-admin.page-header :title="$option->option" subtitle="Modifier l'option." :back="route('options.index')" backLabel="Options" />

<x-admin.form :action="route('options.update', $option)" method="PUT" :cancel="route('options.index')">
    <x-admin.form-section title="Option" icon="bi-stars">
        <x-admin.field name="option" label="Nom de l'option" col="col-md-8" required :value="$option->option" />
    </x-admin.form-section>
</x-admin.form>
@endsection

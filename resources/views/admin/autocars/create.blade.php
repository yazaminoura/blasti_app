@extends('admin.Layout.app')
@section('title', 'Nouvel autocar')

@section('content')
<x-admin.page-header title="Nouvel autocar" subtitle="Ajoutez un véhicule à la flotte." :back="route('autocars.index')" backLabel="Autocars" />

<x-admin.form :action="route('autocars.store')" files :cancel="route('autocars.index')" submit="Ajouter l'autocar" submitIcon="bi-plus-lg">
    @include('admin.autocars._fields', ['autocar' => null])
</x-admin.form>
@endsection

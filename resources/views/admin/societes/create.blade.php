@extends('admin.Layout.app')
@section('title', 'Nouvelle société')

@section('content')
<x-admin.page-header title="Nouvelle société" subtitle="Ajoutez une société de transport partenaire." :back="route('societes.index')" backLabel="Sociétés" />

<x-admin.form :action="route('societes.store')" files :cancel="route('societes.index')" submit="Créer la société" submitIcon="bi-plus-lg">
    @include('admin.societes._fields', ['societe' => null])
</x-admin.form>
@endsection

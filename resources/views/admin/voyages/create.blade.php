@extends('admin.Layout.app')
@section('title', 'Nouveau voyage')

@section('content')
<x-admin.page-header title="Nouveau voyage" subtitle="Programmez un départ et mettez-le en vente." :back="route('voyages.index')" backLabel="Voyages" />

<x-admin.form :action="route('voyages.store')" files :cancel="route('voyages.index')" submit="Créer le voyage" submitIcon="bi-plus-lg">
    @include('admin.voyages._fields', ['voyage' => null])
</x-admin.form>
@endsection

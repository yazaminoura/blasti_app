@extends('admin.Layout.app')
@section('title', 'Modifier le voyage')

@section('content')
<x-admin.page-header :title="'Voyage #' . $voyage->id . ' · ' . $voyage->villeDepart?->ville . ' → ' . $voyage->villeArrivee?->ville"
                     subtitle="Modifiez le trajet, les horaires ou le prix." :back="route('voyages.index')" backLabel="Voyages" />

<x-admin.form :action="route('voyages.update', $voyage->id)" method="PUT" files :cancel="route('voyages.index')">
    @include('admin.voyages._fields', ['voyage' => $voyage])
</x-admin.form>
@endsection

@extends('admin.Layout.app')
@section('title', 'Villes')

@section('content')
@include('admin.partials.simple-list', [
    'items' => $villes, 'field' => 'ville', 'route' => 'villes', 'permission' => 'villes',
    'title' => 'Villes', 'subtitle' => 'Les villes de départ et d\'arrivée proposées dans les voyages.',
    'icon' => 'bi-geo-alt', 'createLabel' => 'Nouvelle ville', 'columnLabel' => 'Ville', 'unit' => 'ville(s)',
    'emptyText' => 'Ajoutez les villes desservies pour pouvoir créer des voyages.',
])
@endsection

@extends('admin.Layout.app')
@section('title', 'Équipements')

@section('content')
@include('admin.partials.simple-list', [
    'items' => $equipements, 'field' => 'equipement', 'route' => 'equipements', 'permission' => 'equipements',
    'title' => 'Équipements', 'subtitle' => 'Les équipements des véhicules (climatisation, toilettes…), à associer ensuite aux autocars.',
    'icon' => 'bi-tools', 'createLabel' => 'Nouvel équipement', 'columnLabel' => 'Équipement', 'unit' => 'équipement(s)',
    'emptyText' => 'Aucun équipement enregistré pour le moment.',
])
@endsection

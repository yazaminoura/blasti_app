@extends('admin.Layout.app')
@section('title', 'Équipements par autocar')

@section('content')
@include('admin.partials.link-list', [
    'items' => $autocarequipements, 'relation' => 'equipement', 'field' => 'equipement', 'route' => 'autocarequipements', 'permission' => 'equipements',
    'title' => 'Équipements par autocar', 'subtitle' => 'Quels équipements sont installés dans quel autocar.',
    'icon' => 'bi-tools', 'itemLabel' => 'Équipement',
])
@endsection

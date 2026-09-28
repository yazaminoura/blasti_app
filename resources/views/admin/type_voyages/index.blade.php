@extends('admin.Layout.app')
@section('title', 'Types de voyage')

@section('content')
@include('admin.partials.simple-list', [
    'items' => $types, 'field' => 'type_voyage', 'route' => 'type_voyages', 'permission' => 'type voyages',
    'title' => 'Types de voyage', 'subtitle' => 'Les catégories de voyage : Confort, Premium, Express…',
    'icon' => 'bi-tags', 'createLabel' => 'Nouveau type', 'columnLabel' => 'Type', 'unit' => 'type(s)',
    'emptyText' => 'Chaque voyage doit avoir un type.',
])
@endsection

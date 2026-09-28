@extends('admin.Layout.app')
@section('title', 'Options')

@section('content')
@include('admin.partials.simple-list', [
    'items' => $options, 'field' => 'option', 'route' => 'options', 'permission' => 'options',
    'title' => 'Options', 'subtitle' => 'Les services proposés à bord (Wi-Fi, prise USB…), à associer ensuite aux autocars.',
    'icon' => 'bi-stars', 'createLabel' => 'Nouvelle option', 'columnLabel' => 'Option', 'unit' => 'option(s)',
    'emptyText' => 'Aucune option disponible pour le moment.',
])
@endsection

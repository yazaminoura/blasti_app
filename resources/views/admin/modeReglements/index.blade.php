@extends('admin.Layout.app')
@section('title', 'Modes de règlement')

@section('content')
@include('admin.partials.simple-list', [
    'items' => $modes, 'field' => 'mode_reglement', 'route' => 'modeReglements', 'permission' => 'mode reglements',
    'title' => 'Modes de règlement', 'subtitle' => 'Les moyens de paiement proposés au client lors de la réservation.',
    'icon' => 'bi-credit-card', 'createLabel' => 'Nouveau mode', 'columnLabel' => 'Mode de règlement', 'unit' => 'mode(s)',
    'emptyText' => 'Ajoutez au moins un mode pour que les clients puissent réserver.',
])
@endsection

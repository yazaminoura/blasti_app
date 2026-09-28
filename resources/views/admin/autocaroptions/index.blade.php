@extends('admin.Layout.app')
@section('title', 'Options par autocar')

@section('content')
@include('admin.partials.link-list', [
    'items' => $autocaroptions, 'relation' => 'option', 'field' => 'option', 'route' => 'autocaroptions', 'permission' => 'options',
    'title' => 'Options par autocar', 'subtitle' => 'Quelles options sont disponibles dans quel autocar.',
    'icon' => 'bi-stars', 'itemLabel' => 'Option',
])
@endsection

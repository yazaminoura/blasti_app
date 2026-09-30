@extends('errors.layout')

@section('code', '503')
@section('title', __('Site en maintenance'))
@section('message', __('Nous améliorons le site pour vous. Revenez dans quelques minutes : vos réservations et vos billets ne sont pas touchés.'))
@section('actions')
    <a href="{{ url('/') }}" class="btn btn-primary">{{ __('Réessayer') }}</a>
@endsection

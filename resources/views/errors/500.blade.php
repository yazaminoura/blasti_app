@extends('errors.layout')

@section('code', '500')
@section('title', __('Un problème technique est survenu'))
@section('message', __('Ce n\'est pas de votre faute : notre équipe a été prévenue. Réessayez dans quelques instants. Si vous étiez en train de réserver, vérifiez « Mes réservations » avant de recommencer.'))
@section('actions')
    <a href="{{ url()->current() }}" class="btn btn-primary">{{ __('Réessayer') }}</a>
    <a href="{{ url('/') }}" class="btn btn-light">{{ __('Accueil') }}</a>
@endsection

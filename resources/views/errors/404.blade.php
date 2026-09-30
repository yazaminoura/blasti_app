@extends('errors.layout')

@section('code', '404')
@section('title', __('Cette page n\'existe pas'))
@section('message', __('Le lien est peut-être incomplet, ou la page a été déplacée. Ce bus-là ne passe pas par ici… mais il y en a beaucoup d\'autres !'))
@section('actions')
    <a href="{{ url('/') }}" class="btn btn-primary">{{ __('Retour à l\'accueil') }}</a>
    <a href="{{ url('/voyages/list') }}" class="btn btn-light">{{ __('Voir les départs') }}</a>
@endsection

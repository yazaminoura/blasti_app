@extends('errors.layout')

@section('code', '429')
@section('title', __('Doucement, trop de tentatives'))
@section('message', __('Vous avez fait beaucoup de demandes en peu de temps. Attendez une minute puis réessayez.'))
@section('actions')
    <a href="{{ url()->previous() }}" class="btn btn-primary">{{ __('Réessayer') }}</a>
    <a href="{{ url('/') }}" class="btn btn-light">{{ __('Accueil') }}</a>
@endsection

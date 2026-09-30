@extends('errors.layout')

@section('code', '419')
@section('title', __('Votre session a expiré'))
@section('message', __('La page est restée ouverte trop longtemps sans activité. Par sécurité, rechargez-la puis recommencez : rien n\'a été enregistré.'))
@section('actions')
    <a href="{{ url()->previous() }}" class="btn btn-primary">{{ __('Recharger la page') }}</a>
    <a href="{{ url('/') }}" class="btn btn-light">{{ __('Accueil') }}</a>
@endsection

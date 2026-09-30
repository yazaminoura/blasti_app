@extends('errors.layout')

@php
    // the reason given by the app (abort(403, '...')), not the generic framework text
    $raison = trim((string) ($exception?->getMessage() ?? ''));
    $raison = in_array($raison, ['', 'Forbidden', 'This action is unauthorized.', 'Invalid signature.'], true) ? null : $raison;
    $signature = ($exception?->getMessage() ?? '') === 'Invalid signature.';
    try {
        $connecte = auth()->check();
    } catch (\Throwable $e) {
        $connecte = false;
    }
@endphp

@section('code', '403')
@section('title', $signature ? __('Ce lien n\'est plus valable') : __('Accès refusé'))
@section('message', $signature
    ? __('Le lien que vous avez ouvert a expiré ou a été modifié. Ouvrez le lien le plus récent reçu par e-mail, ou retrouvez votre billet dans votre espace.')
    : __('Vous n\'avez pas l\'autorisation d\'ouvrir cette page. Si vous pensez que c\'est une erreur, connectez-vous avec le bon compte ou contactez l\'administrateur.'))
@if ($raison)
    @section('detail', $raison)
@endif
@section('actions')
    @if (! $connecte)
        <a href="{{ url('/login') }}" class="btn btn-primary">{{ __('Se connecter') }}</a>
    @endif
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="btn {{ $connecte ? 'btn-primary' : 'btn-light' }}">{{ __('Page précédente') }}</a>
    <a href="{{ url('/') }}" class="btn btn-light">{{ __('Accueil') }}</a>
@endsection

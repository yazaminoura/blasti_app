@extends('admin.Layout.app')
@section('title', 'Nouveau mode de règlement')

@section('content')
<x-admin.page-header title="Nouveau mode de règlement" subtitle="Proposé au client au moment de réserver." :back="route('modeReglements.index')" backLabel="Modes de règlement" />

<x-admin.form :action="route('modeReglements.store')" :cancel="route('modeReglements.index')" submit="Ajouter le mode" submitIcon="bi-plus-lg">
    <x-admin.form-section title="Mode de règlement" icon="bi-credit-card">
        <x-admin.field name="mode_reglement" label="Libellé" col="col-md-8" required autofocus placeholder="Ex : Carte bancaire" />
        <div class="col-12">
            <div class="form-check form-switch">
                <input type="hidden" name="en_ligne" value="0">
                <input class="form-check-input" type="checkbox" role="switch" name="en_ligne" id="en_ligne" value="1" @checked(old('en_ligne', false))>
                <label class="form-check-label fw-semibold" for="en_ligne">Paiement en ligne par carte (CMI)</label>
            </div>
            <div class="form-text">Le client paie sur la page sécurisée du CMI ; la réservation est confirmée après le paiement. Proposé aux clients seulement quand les clés CMI sont renseignées (.env). Décoché = payé à l'embarquement ou en agence.</div>
        </div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input type="hidden" name="en_agence" value="0">
                <input class="form-check-input" type="checkbox" role="switch" name="en_agence" id="en_agence" value="1" @checked(old('en_agence', false))>
                <label class="form-check-label fw-semibold" for="en_agence">Paiement en agence / point de paiement</label>
            </div>
            <div class="form-text">Le client reçoit un code de commande à payer en agence (Wafacash, Cash Plus…) dans les {{ config('safar.agence_delai_heures') }} h ; sans paiement, ses billets sont annulés. Vous marquez le paiement dans Réservations (recherche par code).</div>
        </div>
    </x-admin.form-section>
</x-admin.form>
@endsection

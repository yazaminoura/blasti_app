@extends('admin.Layout.app')
@section('title', 'Modifier le mode de règlement')

@section('content')
<x-admin.page-header :title="$mode->mode_reglement" subtitle="Modifier le mode de règlement." :back="route('modeReglements.index')" backLabel="Modes de règlement" />

<x-admin.form :action="route('modeReglements.update', $mode->id)" method="PUT" :cancel="route('modeReglements.index')">
    <x-admin.form-section title="Mode de règlement" icon="bi-credit-card">
        <x-admin.field name="mode_reglement" label="Libellé" col="col-md-8" required :value="$mode->mode_reglement" />
        <div class="col-12">
            <div class="form-check form-switch">
                <input type="hidden" name="en_ligne" value="0">
                <input class="form-check-input" type="checkbox" role="switch" name="en_ligne" id="en_ligne" value="1" @checked(old('en_ligne', $mode->en_ligne))>
                <label class="form-check-label fw-semibold" for="en_ligne">Paiement en ligne par carte (CMI)</label>
            </div>
            <div class="form-text">Le client paie sur la page sécurisée du CMI ; la réservation est confirmée après le paiement. Proposé aux clients seulement quand les clés CMI sont renseignées (.env). Décoché = payé à l'embarquement ou en agence.</div>
        </div>
    </x-admin.form-section>
</x-admin.form>
@endsection

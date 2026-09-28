@extends('admin.Layout.app')
@section('title', 'Apparence')

@section('content')
@php
    $current = strtoupper(old('couleur', $parametre->couleur ?: config('safar.couleur')));
    $isPreset = array_key_exists($current, $palette);
@endphp

<x-admin.page-header title="Apparence" subtitle="Le nom affiché et la couleur de tout le site : pages publiques, espace client, administration et logo." />

<div class="row g-4 align-items-start">
    <div class="col-xl-7">
        <x-admin.form :action="route('admin.apparence.update')" method="PUT" style="max-width: none;" submit="Enregistrer l'apparence"
                      hint="Appliqué à tout le site dès l'enregistrement.">
            <x-admin.form-section title="Nom affiché" description="Dans le titre des onglets et le pied de page (le logo contient déjà « BLASTI »)." icon="bi-type">
                <x-admin.field name="nom" label="Nom" col="col-md-8" required maxlength="60" :value="$parametre->nom ?: config('safar.nom')" data-sa-name />
            </x-admin.form-section>

            <div class="sa-form-section" style="grid-template-columns: 1fr;">
                <div class="sa-form-section-intro">
                    <span class="sa-tile"><i class="bi bi-palette"></i></span>
                    <div>
                        <h3>Couleur du site</h3>
                        <p>Les trois premières viennent du logo BLASTI. Toutes les couleurs proposées gardent le texte blanc lisible sur les boutons.</p>
                    </div>
                </div>

                <div class="sa-swatches" role="radiogroup" aria-label="Couleur">
                    @foreach ($palette as $hex => $name)
                        <label class="sa-swatch">
                            <input type="radio" name="couleur" value="{{ $hex }}" @checked($current === $hex) data-sa-swatch>
                            <span class="sa-swatch-box">
                                <span class="sa-swatch-color" style="background: {{ $hex }};"></span>
                                <span class="sa-swatch-name">{{ $name }}</span>
                                <span class="sa-swatch-hex">{{ $hex }}</span>
                            </span>
                        </label>
                    @endforeach
                    <label class="sa-swatch">
                        <input type="radio" name="couleur" value="{{ $isPreset ? '#0F766E' : $current }}" @checked(! $isPreset) data-sa-swatch id="custom-swatch">
                        <span class="sa-swatch-box">
                            <span class="sa-swatch-color d-grid" id="custom-color-box"
                                  style="place-items: center; background: {{ $isPreset ? 'var(--sa-surface-3)' : $current }}; color: var(--sa-muted);">
                                @if ($isPreset)<i class="bi bi-plus-lg"></i>@endif
                            </span>
                            <span class="sa-swatch-name">Personnalisée</span>
                            <span class="sa-swatch-hex" id="custom-hex">{{ $isPreset ? 'Au choix' : $current }}</span>
                        </span>
                    </label>
                </div>

                <div class="sa-color-custom">
                    <input type="color" id="color-picker" class="form-control form-control-color" value="{{ $current }}" aria-label="Couleur personnalisée">
                    <div>
                        <div class="fw-semibold small">Couleur personnalisée</div>
                        <div class="sa-contrast" id="contrast-note"></div>
                    </div>
                </div>
                @error('couleur')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </x-admin.form>
    </div>

    <div class="col-xl-5">
        <x-admin.card title="Aperçu" subtitle="Les couleurs changent en direct ; le logo est recoloré à l'enregistrement (quelques secondes)." icon="bi-eye">
            <div class="d-flex align-items-center gap-3 mb-4">
                <x-admin.logo :size="72" />
                <x-admin.logo :size="44" :wordmark="false" />
                <x-admin.logo :size="26" :wordmark="false" />
            </div>

            <div class="sa-preview-pair">
                @foreach (['light' => 'Mode clair', 'dark' => 'Mode sombre'] as $theme => $label)
                    <div class="sa-preview" data-bs-theme="{{ $theme }}">
                        <span class="sa-preview-caption">{{ $label }}</span>
                        <x-admin.logo :size="40" />
                        <div>
                            <span class="sa-nav-link active"><i class="bi bi-ticket-perforated"></i><span>Réservations</span></span>
                            <span class="sa-nav-link"><i class="bi bi-signpost-split"></i><span>Voyages</span></span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter</span>
                            <span class="sa-chip brand dot">À venir</span>
                        </div>
                        <div class="progress"><div class="progress-bar" style="width: 64%"></div></div>
                        <a href="#" onclick="return false" class="small fw-semibold">Voir tout</a>
                    </div>
                @endforeach
            </div>
        </x-admin.card>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var root = document.documentElement;
        var picker = document.getElementById('color-picker');
        var custom = document.getElementById('custom-swatch');
        var customBox = document.getElementById('custom-color-box');
        var customHex = document.getElementById('custom-hex');
        var note = document.getElementById('contrast-note');
        var nameInput = document.querySelector('[data-sa-name]');

        function rgb(hex) {
            var n = parseInt(hex.slice(1), 16);
            return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
        }
        function luminance(c) {
            var v = c.map(function (x) { x /= 255; return x <= 0.03928 ? x / 12.92 : Math.pow((x + 0.055) / 1.055, 2.4); });
            return 0.2126 * v[0] + 0.7152 * v[1] + 0.0722 * v[2];
        }
        function apply(hex) {
            hex = hex.toUpperCase();
            var c = rgb(hex);
            var light = c.map(function (x) { return Math.round(x + (255 - x) * 0.45); });
            root.style.setProperty('--brand', hex);
            root.style.setProperty('--brand-rgb', c.join(', '));
            root.style.setProperty('--brand-rgb-light', light.join(', '));
            var ratio = (1.05) / (luminance(c) + 0.05);
            note.innerHTML = ratio >= 4.5
                ? '<i class="bi bi-check-circle-fill text-success"></i> Texte blanc lisible (contraste ' + ratio.toFixed(1) + ':1)'
                : '<i class="bi bi-exclamation-triangle-fill text-warning"></i> Trop claire : le texte blanc des boutons sera peu lisible (' + ratio.toFixed(1) + ':1, 4.5 conseillé)';
            picker.value = hex;
        }

        document.querySelectorAll('[data-sa-swatch]').forEach(function (radio) {
            radio.addEventListener('change', function () { if (radio.checked) apply(radio.value); });
        });
        picker.addEventListener('input', function () {
            var hex = picker.value.toUpperCase();
            custom.value = hex;
            custom.checked = true;
            customBox.style.background = hex;
            customBox.innerHTML = '';
            customHex.textContent = hex;
            apply(hex);
        });
        if (nameInput) {
            nameInput.addEventListener('input', function () {
                document.querySelectorAll('.brand-logo-text').forEach(function (el) { el.textContent = nameInput.value || ' '; });
            });
        }

        var checked = document.querySelector('[data-sa-swatch]:checked');
        apply(checked ? checked.value : picker.value);
    })();
</script>
@endpush

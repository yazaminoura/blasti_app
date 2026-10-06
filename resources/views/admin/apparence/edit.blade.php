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
        <x-admin.form :action="route('admin.apparence.update')" method="PUT" :files="true" style="max-width: none;" submit="Enregistrer l'apparence"
                      hint="Appliqué à tout le site dès l'enregistrement.">
            <x-admin.form-section title="Nom affiché" description="Nom de votre plateforme (affiché dans le logo, l'en-tête, les onglets et les e-mails)." icon="bi-type">
                <x-admin.field name="nom" label="Nom" col="col-md-8" required maxlength="60" :value="$parametre->nom ?: config('safar.nom')" data-sa-name />
            </x-admin.form-section>

            <div class="sa-form-section sa-logo-section">
                <div class="sa-form-section-intro">
                    <span class="sa-tile"><i class="bi bi-image"></i></span>
                    <div>
                        <h3>Logo de la plateforme</h3>
                        <p>Téléversez votre propre logo, ou laissez vide pour garder le logo automatique avec le nom saisi.</p>
                    </div>
                </div>
                @php
                    $hasCustomMain = filled($parametre->logo);
                    $hasCustomDark = filled($parametre->logo_dark);
                    $hasAnyCustom = $hasCustomMain || $hasCustomDark;

                    $mainLogoUrl = $hasCustomMain ? \App\Models\Ville::resolveImageUrl($parametre->logo) : \App\Support\BrandImages::logoUrl();
                    $darkLogoUrl = $hasCustomDark ? \App\Models\Ville::resolveImageUrl($parametre->logo_dark) : ($hasCustomMain ? \App\Models\Ville::resolveImageUrl($parametre->logo) : \App\Support\BrandImages::logoDarkUrl());
                @endphp

                {{-- Status Banner --}}
                @if ($hasAnyCustom)
                    <div class="sa-logo-status-card p-3 mb-3 rounded-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="sa-logo-status-icon bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                <i class="bi bi-patch-check-fill fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-body d-flex align-items-center gap-2">
                                    <span>Logo personnalisé actif</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle extra-small">En ligne</span>
                                </div>
                                <div class="small text-muted">Votre image est utilisée sur tout le site et l'administration.</div>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                            <input class="form-check-input" type="checkbox" name="supprimer_logo" id="supprimer_logo" value="1" role="switch">
                            <label class="form-check-label small fw-semibold text-danger cursor-pointer mb-0" for="supprimer_logo">
                                <i class="bi bi-trash3 me-1"></i> Revenir au logo automatique
                            </label>
                        </div>
                    </div>
                @else
                    <div class="sa-logo-status-card p-3 mb-3 rounded-3 d-flex align-items-center gap-3">
                        <div class="sa-logo-status-icon bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                            <i class="bi bi-magic fs-5"></i>
                        </div>
                        <div class="small text-muted">
                            <strong class="text-body">Logo automatique actif.</strong> Généré avec le nom <strong class="brand-logo-text">{{ strtoupper($parametre->nom ?: config('safar.nom')) }}</strong> et la couleur du site.
                        </div>
                    </div>
                @endif

                <div class="row g-3">
                    {{-- 1. Logo principal (Fond clair) --}}
                    <div class="col-md-6">
                        <div class="sa-logo-card h-100 p-3 rounded-3 d-flex flex-column">
                            {{-- Card Header --}}
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div>
                                    <div class="fw-bold text-body d-flex align-items-center gap-2">
                                        <i class="bi bi-sun-fill text-warning"></i>
                                        <span>Logo principal</span>
                                    </div>
                                    <div class="extra-small text-muted">En-tête clair, e-mails, tickets PDF</div>
                                </div>
                                <span class="badge bg-light text-dark border extra-small">Fond clair</span>
                            </div>

                            {{-- Preview Stage --}}
                            <div class="sa-logo-stage sa-logo-stage-light rounded-3 position-relative mb-3 d-flex align-items-center justify-content-center p-3" id="stage_logo">
                                <span class="badge bg-white text-secondary border position-absolute top-0 end-0 m-2 extra-small shadow-sm" id="badge_mode_logo" data-initial-text="{{ $hasCustomMain ? 'Personnalisé' : 'Automatique' }}">
                                    {{ $hasCustomMain ? 'Personnalisé' : 'Automatique' }}
                                </span>
                                <img src="{{ $mainLogoUrl }}" 
                                     alt="Logo principal" 
                                     id="preview_img_logo" 
                                     data-initial-src="{{ $mainLogoUrl }}"
                                     class="sa-logo-stage-img">
                            </div>

                            {{-- Dropzone & Upload Button --}}
                            <div class="sa-logo-dropzone rounded-3 p-3 text-center position-relative mb-2 @error('logo') is-invalid @enderror" id="dropzone_logo">
                                <input type="file" name="logo" id="f_logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" 
                                       class="sa-logo-file-input" data-sa-target="logo">
                                
                                <div class="sa-logo-dropzone-prompt" id="prompt_logo">
                                    <div class="sa-logo-drop-icon mb-1">
                                        <i class="bi bi-cloud-arrow-up fs-2 text-primary"></i>
                                    </div>
                                    <div class="fw-semibold text-body small mb-1">
                                        <span class="text-primary text-decoration-underline">Choisir une image</span> ou glisser ici
                                    </div>
                                    <div class="text-muted extra-small">
                                        PNG, SVG, WEBP ou JPG · Max 2 Mo · H: 40-60 px
                                    </div>
                                </div>

                                {{-- File selected status --}}
                                <div class="sa-logo-file-info d-none text-start p-2 rounded-2" id="info_logo">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                                            <i class="bi bi-file-earmark-image-fill text-primary fs-5 flex-shrink-0"></i>
                                            <div class="overflow-hidden">
                                                <div class="fw-semibold small text-truncate text-body" id="name_logo"></div>
                                                <div class="text-muted extra-small" id="size_logo"></div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0 py-1 px-2 flex-shrink-0" id="cancel_logo" title="Annuler la sélection">
                                            <i class="bi bi-x-circle-fill"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @error('logo')
                                <div class="text-danger small mt-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $message }}</div>
                            @enderror

                            <div class="extra-small text-muted mt-auto pt-2">
                                <i class="bi bi-info-circle me-1"></i>Recommandé : image transparente (hauteur 40-60 px).
                            </div>
                        </div>
                    </div>

                    {{-- 2. Logo fond sombre (Optionnel) --}}
                    <div class="col-md-6">
                        <div class="sa-logo-card h-100 p-3 rounded-3 d-flex flex-column">
                            {{-- Card Header --}}
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div>
                                    <div class="fw-bold text-body d-flex align-items-center gap-2">
                                        <i class="bi bi-moon-stars-fill text-info"></i>
                                        <span>Logo fond sombre</span>
                                        <span class="badge bg-secondary-subtle text-secondary fw-normal extra-small">Optionnel</span>
                                    </div>
                                    <div class="extra-small text-muted">Pied de page sombre, mode nuit</div>
                                </div>
                                <span class="badge bg-dark text-white border border-secondary extra-small">Fond sombre</span>
                            </div>

                            {{-- Preview Stage (Dark Canvas) --}}
                            <div class="sa-logo-stage sa-logo-stage-dark rounded-3 position-relative mb-3 d-flex align-items-center justify-content-center p-3" id="stage_logo_dark">
                                <span class="badge bg-dark text-light border border-secondary position-absolute top-0 end-0 m-2 extra-small shadow-sm" id="badge_mode_logo_dark" data-initial-text="{{ $hasCustomDark ? 'Personnalisé' : ($hasCustomMain ? 'Identique principal' : 'Automatique') }}">
                                    {{ $hasCustomDark ? 'Personnalisé' : ($hasCustomMain ? 'Identique principal' : 'Automatique') }}
                                </span>
                                <img src="{{ $darkLogoUrl }}" 
                                     alt="Logo fond sombre" 
                                     id="preview_img_logo_dark" 
                                     data-initial-src="{{ $darkLogoUrl }}"
                                     class="sa-logo-stage-img">
                            </div>

                            {{-- Dropzone & Upload Button --}}
                            <div class="sa-logo-dropzone rounded-3 p-3 text-center position-relative mb-2 @error('logo_dark') is-invalid @enderror" id="dropzone_logo_dark">
                                <input type="file" name="logo_dark" id="f_logo_dark" accept="image/png,image/jpeg,image/svg+xml,image/webp" 
                                       class="sa-logo-file-input" data-sa-target="logo_dark">
                                
                                <div class="sa-logo-dropzone-prompt" id="prompt_logo_dark">
                                    <div class="sa-logo-drop-icon mb-1">
                                        <i class="bi bi-cloud-arrow-up fs-2 text-info"></i>
                                    </div>
                                    <div class="fw-semibold text-body small mb-1">
                                        <span class="text-primary text-decoration-underline">Choisir une image</span> ou glisser ici
                                    </div>
                                    <div class="text-muted extra-small">
                                        Version blanche ou claire · PNG/SVG transparent
                                    </div>
                                </div>

                                {{-- File selected status --}}
                                <div class="sa-logo-file-info d-none text-start p-2 rounded-2" id="info_logo_dark">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                                            <i class="bi bi-file-earmark-image-fill text-info fs-5 flex-shrink-0"></i>
                                            <div class="overflow-hidden">
                                                <div class="fw-semibold small text-truncate text-body" id="name_logo_dark"></div>
                                                <div class="text-muted extra-small" id="size_logo_dark"></div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0 py-1 px-2 flex-shrink-0" id="cancel_logo_dark" title="Annuler la sélection">
                                            <i class="bi bi-x-circle-fill"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @error('logo_dark')
                                <div class="text-danger small mt-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $message }}</div>
                            @enderror

                            <div class="extra-small text-muted mt-auto pt-2">
                                <i class="bi bi-info-circle me-1"></i>Si vide, le logo principal sera utilisé sur fond sombre.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
            function updateName() {
                var val = (nameInput.value || '').trim();
                document.querySelectorAll('.brand-logo-text').forEach(function (el) { el.textContent = (val || ' ').toUpperCase(); });
                var defaultBoxes = document.querySelectorAll('.logo-default-box');
                var dynamicBoxes = document.querySelectorAll('.logo-dynamic-box');
                if (defaultBoxes.length > 0 && dynamicBoxes.length > 0) {
                    var isBlasti = val.toLowerCase() === 'blasti' || val === '';
                    defaultBoxes.forEach(function (b) { b.classList.toggle('d-none', !isBlasti); });
                    dynamicBoxes.forEach(function (b) { b.classList.toggle('d-none', isBlasti); });
                }
            }
            nameInput.addEventListener('input', updateName);
            updateName();
        }

        // ---- Platform Logo Drag & Drop and Live Preview Sync ----
        ['logo', 'logo_dark'].forEach(function (key) {
            var input = document.getElementById('f_' + key);
            var dropzone = document.getElementById('dropzone_' + key);
            var cancelBtn = document.getElementById('cancel_' + key);
            if (!input || !dropzone) return;

            // Drag and drop visual cues
            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'dragend', 'drop'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('is-dragover');
                });
            });

            // File selection
            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file) return;

                if (!file.type.startsWith('image/')) {
                    alert('Format non supporté : veuillez choisir une image (PNG, SVG, WEBP ou JPG).');
                    input.value = '';
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    alert('Image trop volumineuse : la taille maximale est de 2 Mo.');
                    input.value = '';
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (e) {
                    var dataUrl = e.target.result;
                    var previewImg = document.getElementById('preview_img_' + key);
                    var badgeMode = document.getElementById('badge_mode_' + key);
                    var promptBox = document.getElementById('prompt_' + key);
                    var infoBox = document.getElementById('info_' + key);
                    var nameEl = document.getElementById('name_' + key);
                    var sizeEl = document.getElementById('size_' + key);

                    if (previewImg) previewImg.src = dataUrl;
                    if (badgeMode) {
                        badgeMode.textContent = 'Nouvelle image';
                        badgeMode.className = 'badge bg-warning text-dark border position-absolute top-0 end-0 m-2 extra-small shadow-sm';
                    }
                    if (nameEl) nameEl.textContent = file.name;
                    if (sizeEl) sizeEl.textContent = (file.size / 1024).toFixed(1) + ' Ko';
                    if (promptBox) promptBox.classList.add('d-none');
                    if (infoBox) infoBox.classList.remove('d-none');

                    // If user selects a new file, uncheck "supprimer_logo" if it was checked
                    var sup = document.getElementById('supprimer_logo');
                    if (sup && sup.checked) {
                        sup.checked = false;
                        sup.dispatchEvent(new Event('change'));
                    }

                    // Live sync with right-side preview cards
                    var targetSelector = key === 'logo_dark' ? '.logo-on-dark' : '.logo-on-light';
                    document.querySelectorAll(targetSelector).forEach(function (img) {
                        if (img.tagName === 'IMG') {
                            img.src = dataUrl;
                        }
                    });
                };
                reader.readAsDataURL(file);
            });

            // Cancel button
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    input.value = '';
                    var previewImg = document.getElementById('preview_img_' + key);
                    var badgeMode = document.getElementById('badge_mode_' + key);
                    var promptBox = document.getElementById('prompt_' + key);
                    var infoBox = document.getElementById('info_' + key);

                    if (previewImg && previewImg.dataset.initialSrc) {
                        previewImg.src = previewImg.dataset.initialSrc;
                    }
                    if (badgeMode) {
                        var initialText = badgeMode.dataset.initialText || (key === 'logo_dark' ? 'Optionnel' : 'Automatique');
                        badgeMode.textContent = initialText;
                        badgeMode.className = key === 'logo_dark'
                            ? 'badge bg-dark text-light border border-secondary position-absolute top-0 end-0 m-2 extra-small shadow-sm'
                            : 'badge bg-white text-secondary border position-absolute top-0 end-0 m-2 extra-small shadow-sm';
                    }
                    if (promptBox) promptBox.classList.remove('d-none');
                    if (infoBox) infoBox.classList.add('d-none');

                    // Revert right-side preview image
                    var targetSelector = key === 'logo_dark' ? '.logo-on-dark' : '.logo-on-light';
                    var initialUrl = previewImg ? previewImg.dataset.initialSrc : null;
                    if (initialUrl) {
                        document.querySelectorAll(targetSelector).forEach(function (img) {
                            if (img.tagName === 'IMG') {
                                img.src = initialUrl;
                            }
                        });
                    }
                });
            }
        });

        // Supprimer logo toggle
        var supprimerLogo = document.getElementById('supprimer_logo');
        if (supprimerLogo) {
            supprimerLogo.addEventListener('change', function () {
                var stageLight = document.getElementById('stage_logo');
                var stageDark = document.getElementById('stage_logo_dark');
                if (this.checked) {
                    if (stageLight) stageLight.classList.add('is-marked-delete');
                    if (stageDark) stageDark.classList.add('is-marked-delete');
                } else {
                    if (stageLight) stageLight.classList.remove('is-marked-delete');
                    if (stageDark) stageDark.classList.remove('is-marked-delete');
                }
            });
        }

        var checked = document.querySelector('[data-sa-swatch]:checked');
        apply(checked ? checked.value : picker.value);
    })();
</script>
@endpush

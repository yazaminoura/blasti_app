@props(['size' => 36, 'wordmark' => true])

@php
    $hasCustom = \App\Support\BrandImages::hasCustomLogo();
    $isDefault = \App\Support\BrandImages::isDefaultBrand();
    $nom = config('safar.nom', 'Blasti');
@endphp

<span {{ $attributes->merge(['class' => 'brand-logo' . ($wordmark ? ' brand-logo-full' : '')]) }}>
    @if ($wordmark)
        @if ($hasCustom)
            <img src="{{ \App\Support\BrandImages::logoUrl() }}" alt="{{ $nom }}" class="logo-on-light" style="height: {{ $size }}px; width: auto; max-width: 180px; object-fit: contain;">
            <img src="{{ \App\Support\BrandImages::logoDarkUrl() }}" alt="{{ $nom }}" class="logo-on-dark" style="height: {{ $size }}px; width: auto; max-width: 180px; object-fit: contain;">
            <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" alt="{{ $nom }}" class="logo-rail" style="height: 26px; width: auto;">
        @elseif (! $isDefault)
            <span class="logo-on-light d-inline-flex align-items-center gap-2">
                <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" alt="{{ $nom }}" style="height: {{ min($size, 38) }}px; width: auto;">
                <span class="brand-logo-text" style="font-size: {{ max(1.1, min(1.4, $size * 0.035)) }}rem;">{{ $nom }}</span>
            </span>
            <span class="logo-on-dark d-inline-flex align-items-center gap-2">
                <img src="{{ \App\Support\BrandImages::url('blasti-hero-bus.png') }}" alt="{{ $nom }}" style="height: {{ min($size, 38) }}px; width: auto;">
                <span class="brand-logo-text text-white" style="font-size: {{ max(1.1, min(1.4, $size * 0.035)) }}rem;">{{ $nom }}</span>
            </span>
            <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" alt="{{ $nom }}" class="logo-rail" style="height: 26px; width: auto;">
        @else
            <span class="logo-default-box">
                <img src="{{ \App\Support\BrandImages::url('blasti-logo.png') }}" alt="{{ $nom }}" class="logo-on-light" style="height: {{ $size }}px; width: auto;">
                <img src="{{ \App\Support\BrandImages::url('blasti-logo-dark.png') }}" alt="{{ $nom }}" class="logo-on-dark" style="height: {{ $size }}px; width: auto;">
            </span>
            <span class="logo-dynamic-box d-none">
                <span class="logo-on-light d-inline-flex align-items-center gap-2">
                    <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" alt="{{ $nom }}" style="height: {{ min($size, 38) }}px; width: auto;">
                    <span class="brand-logo-text" style="font-size: {{ max(1.1, min(1.4, $size * 0.035)) }}rem;">{{ $nom }}</span>
                </span>
                <span class="logo-on-dark d-inline-flex align-items-center gap-2">
                    <img src="{{ \App\Support\BrandImages::url('blasti-hero-bus.png') }}" alt="{{ $nom }}" style="height: {{ min($size, 38) }}px; width: auto;">
                    <span class="brand-logo-text text-white" style="font-size: {{ max(1.1, min(1.4, $size * 0.035)) }}rem;">{{ $nom }}</span>
                </span>
            </span>
            <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" alt="{{ $nom }}" class="logo-rail" style="height: 26px; width: auto;">
        @endif
    @else
        <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" alt="{{ $nom }}" style="height: {{ $size }}px; width: auto;">
    @endif
</span>

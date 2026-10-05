@props(['size' => 36, 'wordmark' => true])

@php
    $hasCustom = \App\Support\BrandImages::hasCustomLogo();
    $isDefault = \App\Support\BrandImages::isDefaultBrand();
    $nom = config('safar.nom', 'Blasti');
    $busH = max(18, (int) round($size * 0.58));
    $fontS = max(10, (int) round($size * 0.25));
@endphp

<span {{ $attributes->merge(['class' => 'brand-logo' . ($wordmark ? ' brand-logo-full' : '')]) }}>
    @if ($wordmark)
        @if ($hasCustom)
            <img src="{{ \App\Support\BrandImages::logoUrl() }}" alt="{{ $nom }}" class="logo-on-light" style="height: {{ $size }}px; width: auto; max-width: 180px; object-fit: contain;">
            <img src="{{ \App\Support\BrandImages::logoDarkUrl() }}" alt="{{ $nom }}" class="logo-on-dark" style="height: {{ $size }}px; width: auto; max-width: 180px; object-fit: contain;">
            <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" alt="{{ $nom }}" class="logo-rail" style="height: 26px; width: auto;">
        @else
            <span class="logo-default-box {{ $isDefault ? '' : 'd-none' }}">
                <img src="{{ \App\Support\BrandImages::url('blasti-logo.png') }}" alt="{{ $nom }}" class="logo-on-light" style="height: {{ $size }}px; width: auto;">
                <img src="{{ \App\Support\BrandImages::url('blasti-logo-dark.png') }}" alt="{{ $nom }}" class="logo-on-dark" style="height: {{ $size }}px; width: auto;">
            </span>
            <span class="logo-dynamic-box {{ $isDefault ? 'd-none' : '' }}">
                <span class="logo-on-light brand-stacked">
                    <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" alt="{{ $nom }}" class="brand-bus-img" style="height: {{ $busH }}px; width: auto;">
                    <span class="brand-logo-name brand-logo-text" style="font-size: {{ $fontS }}px;">{{ strtoupper($nom) }}</span>
                    <span class="brand-logo-line"></span>
                </span>
                <span class="logo-on-dark brand-stacked">
                    <img src="{{ \App\Support\BrandImages::url('blasti-hero-bus.png') }}" alt="{{ $nom }}" class="brand-bus-img" style="height: {{ $busH }}px; width: auto;">
                    <span class="brand-logo-name brand-logo-text" style="font-size: {{ $fontS }}px;">{{ strtoupper($nom) }}</span>
                    <span class="brand-logo-line"></span>
                </span>
            </span>
            <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" alt="{{ $nom }}" class="logo-rail" style="height: 26px; width: auto;">
        @endif
    @else
        <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" alt="{{ $nom }}" class="logo-on-light" style="height: {{ $size }}px; width: auto;">
        <img src="{{ \App\Support\BrandImages::url('blasti-hero-bus.png') }}" alt="{{ $nom }}" class="logo-on-dark" style="height: {{ $size }}px; width: auto;">
    @endif
</span>

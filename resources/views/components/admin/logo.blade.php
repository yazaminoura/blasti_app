@props(['size' => 36, 'wordmark' => true])

{{--
    BLASTI logo, same as the header of the public site.
    wordmark = full logo (bus + "BLASTI"), with a white-text version in dark mode; otherwise the bus alone.
    $size = height in px.
--}}
<span {{ $attributes->merge(['class' => 'brand-logo' . ($wordmark ? ' brand-logo-full' : '')]) }}>
    @if ($wordmark)
        <img src="{{ \App\Support\BrandImages::url('blasti-logo.png') }}" alt="{{ config('safar.nom') }}" class="logo-on-light" style="height: {{ $size }}px; width: auto;">
        <img src="{{ \App\Support\BrandImages::url('blasti-logo-dark.png') }}" alt="{{ config('safar.nom') }}" class="logo-on-dark" style="height: {{ $size }}px; width: auto;">
        <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" alt="{{ config('safar.nom') }}" class="logo-rail" style="height: 26px; width: auto;">
    @else
        <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" alt="{{ config('safar.nom') }}" style="height: {{ $size }}px; width: auto;">
    @endif
</span>

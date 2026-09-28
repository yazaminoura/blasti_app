@props(['label', 'value', 'icon', 'tone' => null])

<div class="sa-card sa-stat">
    <div class="sa-stat-top">
        <div>
            <div class="sa-stat-label">{{ $label }}</div>
            <div class="sa-stat-value">{{ $value }}</div>
        </div>
        <span class="sa-tile lg {{ $tone }}"><i class="bi {{ $icon }}"></i></span>
    </div>
    @if (trim($slot))
        <div class="sa-stat-foot">{{ $slot }}</div>
    @endif
</div>

@props(['title' => null, 'subtitle' => null, 'icon' => null, 'tone' => null, 'flush' => false])

{{-- Surface card. "flush" removes the body padding (tables). Slots: actions (header right), footer. --}}
<section {{ $attributes->merge(['class' => 'sa-card']) }}>
    @if ($title || isset($actions))
        <header class="sa-card-header">
            <div class="sa-card-title">
                @if ($icon)
                    <span class="sa-tile {{ $tone }}"><i class="bi {{ $icon }}"></i></span>
                @endif
                <div>
                    @if ($title)<h2>{{ $title }}</h2>@endif
                    @if ($subtitle)<p>{{ $subtitle }}</p>@endif
                </div>
            </div>
            @isset($actions)
                <div class="d-flex flex-wrap align-items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    @if ($flush)
        {{ $slot }}
    @else
        <div class="sa-card-body">{{ $slot }}</div>
    @endif

    @isset($footer)
        <footer class="sa-card-footer">{{ $footer }}</footer>
    @endisset
</section>

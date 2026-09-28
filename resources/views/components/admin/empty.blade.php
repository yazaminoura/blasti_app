@props(['icon' => 'bi-inbox', 'title' => 'Aucun élément', 'text' => null])

<div class="sa-empty">
    <span class="sa-tile"><i class="bi {{ $icon }}"></i></span>
    <h3>{{ $title }}</h3>
    @if ($text)<p>{{ $text }}</p>@endif
    {{ $slot }}
</div>

@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => 'Retour'])

{{-- Page title with optional back link; the slot holds the action buttons --}}
<div class="sa-page-header">
    <div>
        @if ($back)
            <a href="{{ $back }}" class="sa-back"><i class="bi bi-arrow-left"></i> {{ $backLabel }}</a>
        @endif
        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>
    @if (trim($slot))
        <div class="sa-page-actions">{{ $slot }}</div>
    @endif
</div>

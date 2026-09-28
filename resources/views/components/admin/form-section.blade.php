@props(['title', 'description' => null, 'icon' => null])

{{-- One block of a form: explanation on the left, fields (a Bootstrap .row) on the right --}}
<div class="sa-form-section">
    <div class="sa-form-section-intro">
        @if ($icon)
            <span class="sa-tile"><i class="bi {{ $icon }}"></i></span>
        @endif
        <div>
            <h3>{{ $title }}</h3>
            @if ($description)<p>{{ $description }}</p>@endif
        </div>
    </div>
    <div class="row g-3 align-content-start">
        {{ $slot }}
    </div>
</div>

@props(['action'])

{{-- GET filter bar above a list. Slot = Bootstrap columns with the filter fields. --}}
@php $active = collect(request()->except('page'))->filter(fn ($v) => filled($v))->count(); @endphp
<form method="GET" action="{{ $action }}" class="sa-card sa-filters mb-3">
    <div class="row g-3 align-items-end">
        {{ $slot }}
        <div class="col d-flex gap-2 justify-content-end align-items-end">
            @if ($active)
                <a href="{{ $action }}" class="btn btn-soft"><i class="bi bi-x-lg"></i> Effacer ({{ $active }})</a>
            @endif
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrer</button>
        </div>
    </div>
</form>

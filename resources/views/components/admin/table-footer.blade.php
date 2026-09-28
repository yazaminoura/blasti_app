@props(['paginator', 'label' => 'élément(s)'])

{{-- "1–10 sur 42" + pagination links under a table (also accepts a plain collection) --}}
@if ($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
    @if ($paginator->total() > 0)
        <div class="sa-table-footer">
            <span>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} sur {{ $paginator->total() }} {{ $label }}</span>
            @if ($paginator->hasPages())
                {{ $paginator->onEachSide(1)->links() }}
            @endif
        </div>
    @endif
@elseif (count($paginator) > 0)
    <div class="sa-table-footer"><span>{{ count($paginator) }} {{ $label }}</span></div>
@endif

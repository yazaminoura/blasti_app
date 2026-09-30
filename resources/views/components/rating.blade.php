@props(['societe'])

{{-- Company rating from the published reviews: ★ 4,3 (12). Nothing until the first review. --}}
@php $note = once(fn () => \App\Models\Avis::notesParSociete())->get((int) $societe); @endphp
@if ($note)
    <span {{ $attributes->merge(['class' => 'bl-rating']) }} title="{{ trans_choice('{1} :n avis|[2,*] :n avis', $note['nombre'], ['n' => $note['nombre']]) }}">
        <i class="isax isax-star5"></i>{{ number_format($note['moyenne'], 1, ',', '') }}
        <span>({{ $note['nombre'] }})</span>
    </span>
@endif

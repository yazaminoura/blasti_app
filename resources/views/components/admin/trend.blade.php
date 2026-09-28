@props(['value' => null, 'label' => 'vs mois dernier'])

{{-- Month-over-month evolution; $value is a % (int) or null when there is no previous data --}}
@if (is_null($value))
    <span>Pas de données le mois dernier</span>
@elseif ($value >= 0)
    <span class="sa-trend up"><i class="bi bi-arrow-up-short"></i>{{ $value }}%</span> <span>{{ $label }}</span>
@else
    <span class="sa-trend down"><i class="bi bi-arrow-down-short"></i>{{ abs($value) }}%</span> <span>{{ $label }}</span>
@endif

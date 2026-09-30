{{-- Refund policy table, built from config('safar.annulation.paliers') (calendar days). Param: $compact (small text under a form) --}}
@php
    // days before the departure day => % refunded
    $steps = config('safar.annulation.paliers', []);
    krsort($steps);
    $rows = [];
    foreach ($steps as $jours => $percent) {
        $label = match (true) {
            $jours === 0 => __('Le jour du départ'),
            $jours === 1 => __('La veille du départ'),
            default => __(':n jours ou plus avant le départ', ['n' => $jours]),
        };
        $rows[] = [$label, $percent];
    }
@endphp
@if (! empty($compact))
    <div class="form-text fs-12">
        <strong>{{ __('Annulation :') }}</strong>
        @foreach ($rows as [$label, $percent])
            {{ $label }} : {{ __(':p % remboursé', ['p' => $percent]) }}@if (! $loop->last) · @endif
        @endforeach
        · {{ __('après le départ : impossible.') }}
    </div>
@else
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-2">
            <thead><tr><th>{{ __('Vous annulez') }}</th><th class="text-end">{{ __('Remboursé') }}</th></tr></thead>
            <tbody>
                @foreach ($rows as [$label, $percent])
                    <tr><td>{{ $label }}</td><td class="text-end fw-semibold">{{ $percent }} %</td></tr>
                @endforeach
                <tr><td>{{ __('Après le départ du bus') }}</td><td class="text-end fw-semibold text-danger">{{ __('Annulation impossible') }}</td></tr>
            </tbody>
        </table>
    </div>
    <p class="fs-13 text-muted mb-0">{{ __('Un billet pas encore payé (paiement à l\'embarquement) s\'annule gratuitement. Si la société annule le voyage, vous êtes remboursé à 100 %.') }}</p>
@endif

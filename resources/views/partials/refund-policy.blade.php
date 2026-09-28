{{-- Refund policy table, built from config('safar.annulation.paliers'). Param: $compact (small text under a form) --}}
@php
    $steps = config('safar.annulation.paliers', []);
    krsort($steps);
    $duree = fn (int $h) => $h >= 24 && $h % 24 === 0
        ? trans_choice(':n jour|:n jours', intdiv($h, 24), ['n' => intdiv($h, 24)])
        : __(':n h', ['n' => $h]);
    $rows = [];
    $upper = null;
    foreach ($steps as $hours => $percent) {
        $label = match (true) {
            $upper === null => __('Plus de :d avant le départ', ['d' => $duree($hours)]),
            $hours === 0 => __('Moins de :d avant le départ', ['d' => $duree($upper)]),
            default => __('Entre :a et :b avant le départ', ['a' => $duree($hours), 'b' => $duree($upper)]),
        };
        $rows[] = [$label, $percent];
        $upper = $hours;
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

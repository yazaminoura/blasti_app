@extends('admin.Layout.app')
@section('title', 'Programmer')

@section('content')
@php
    $d = $voyage->departAt();
    $jours = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'];
    $choisis = array_map('intval', old('jours', array_keys($jours)));
@endphp

<x-admin.page-header title="Programmer ce voyage"
                     :subtitle="$voyage->villeDepart?->ville . ' → ' . $voyage->villeArrivee?->ville . ' · départ ' . $d->format('H:i') . ' · ' . $voyage->autocar?->societe?->raison_social . ' ' . $voyage->autocar?->matricule"
                     :back="route('voyages.index')" backLabel="Voyages" />

<x-admin.form :action="route('voyages.programmer.store', $voyage)" method="POST" submit="Créer les voyages"
              hint="Même autocar, mêmes horaires, même prix et mêmes arrêts. Les jours où l'autocar est déjà en route sont ignorés.">
    <x-admin.form-section title="Période" description="Au maximum 3 mois à la fois." icon="bi-calendar-range">
        <x-admin.field name="du" label="Du" type="date" col="col-md-6" required :value="now()->addDay()->max($d->copy()->addDay())->toDateString()" />
        <x-admin.field name="au" label="Au" type="date" col="col-md-6" required :value="now()->addDay()->max($d->copy()->addDay())->addDays(29)->toDateString()" />
    </x-admin.form-section>

    <x-admin.form-section title="Jours de la semaine" description="Le voyage part ces jours-là à la même heure." icon="bi-calendar-week">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
                @foreach ($jours as $n => $label)
                    <input type="checkbox" class="btn-check" name="jours[]" value="{{ $n }}" id="jour-{{ $n }}" @checked(in_array($n, $choisis, true))>
                    <label class="btn btn-outline-primary rounded-pill px-3" for="jour-{{ $n }}">{{ $label }}</label>
                @endforeach
            </div>
            @error('jours')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </div>
    </x-admin.form-section>

    <x-admin.form-section title="Ce qui sera copié" icon="bi-copy">
        <div class="col-12">
            <ul class="mb-0">
                <li>Départ à <strong>{{ $d->format('H:i') }}</strong>, arrivée à <strong>{{ $voyage->arriveeAt()->format('H:i') }}</strong></li>
                <li>Prix du trajet complet : <strong>{{ number_format($voyage->prix, 2, ',', ' ') }} DH</strong></li>
                <li>Arrêts : {{ $voyage->arrets->map(fn ($a) => $a->ville?->ville . ' ' . $a->passage_at->format('H:i'))->join(' → ') }}</li>
            </ul>
        </div>
    </x-admin.form-section>
</x-admin.form>
@endsection

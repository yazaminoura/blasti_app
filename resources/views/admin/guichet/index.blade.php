@extends('admin.Layout.app')
@section('title', 'Guichet')

@section('content')
<x-admin.page-header title="Guichet" subtitle="Vendre un billet à un voyageur présent : départ, sièges, voyageur, encaissement. Le billet part par e-mail, WhatsApp ou à l'impression." />

{{-- 1. the departure --}}
<x-admin.card class="mb-3">
    <form method="GET" action="{{ route('reservation.admin.guichet') }}" class="row g-2 align-items-end">
        <div class="col-md-4 col-6">
            <label class="form-label" for="de">Départ de</label>
            <select name="de" id="de" class="form-select">
                <option value="">Toutes les villes</option>
                @foreach ($villes as $v)
                    <option value="{{ $v->id }}" @selected($de === $v->id)>{{ $v->ville }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 col-6">
            <label class="form-label" for="a">Arrivée à</label>
            <select name="a" id="a" class="form-select">
                <option value="">Toutes les villes</option>
                @foreach ($villes as $v)
                    <option value="{{ $v->id }}" @selected($a === $v->id)>{{ $v->ville }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label" for="date">Date</label>
            <input type="date" name="date" id="date" class="form-control" value="{{ $date }}" min="{{ today()->toDateString() }}">
        </div>
        <div class="col-md-2 col-6">
            <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Chercher</button>
        </div>
    </form>
</x-admin.card>

<x-admin.card :title="'Départs à partir du ' . \Carbon\Carbon::parse($date)->translatedFormat('l d F')" subtitle="Les plus proches d'abord, sur 7 jours." icon="bi-signpost-split" flush class="mb-3">
    @if ($departs->isEmpty())
        <x-admin.empty icon="bi-calendar-x" title="Aucun départ" text="Aucun bus ne fait ce trajet dans les 7 jours à partir de cette date. Essayez une autre date ou d'autres villes." />
    @else
        <div class="sa-table-wrap">
            <table class="table sa-table align-middle">
                <thead><tr><th>Départ</th><th>Trajet</th><th>Compagnie · bus</th><th>Places libres</th><th class="text-end">Prix</th><th></th></tr></thead>
                <tbody>
                    @foreach ($departs as $d)
                        @php $actif = $choix?->voyage->id === $d->voyage->id; @endphp
                        <tr @class(['table-active' => $actif])>
                            <td class="sa-num">
                                <div class="sa-strong">{{ $d->depart->passage_at->format('H:i') }}</div>
                                <div class="sa-sub">{{ $d->depart->passage_at->isToday() ? 'Aujourd\'hui' : ($d->depart->passage_at->isTomorrow() ? 'Demain' : ucfirst($d->depart->passage_at->translatedFormat('D d/m'))) }}</div>
                            </td>
                            <td><span class="sa-route">{{ $d->depart->ville?->ville ?? $d->voyage->villeDepart?->ville }} <i class="bi bi-arrow-right"></i> {{ $d->arrivee->ville?->ville ?? $d->voyage->villeArrivee?->ville }}</span>
                                <div class="sa-sub">arrivée {{ $d->arrivee->passage_at->format('H:i') }}</div></td>
                            <td>{{ $d->voyage->autocar?->societe?->raison_social }}<div class="sa-sub">{{ $d->voyage->autocar?->matricule }}</div></td>
                            <td><span class="sa-chip {{ $d->libres ? ($d->libres < 5 ? 'warning' : 'success') : 'danger' }}">{{ $d->libres }} / {{ $d->voyage->autocar?->nbr_siege }}</span></td>
                            <td class="text-end sa-num sa-strong">{{ number_format($d->prix, 2, ',', ' ') }} DH</td>
                            <td class="text-end">
                                @if ($d->libres)
                                    <a href="{{ route('reservation.admin.guichet', ['de' => $de, 'a' => $a, 'date' => $date, 'voyage' => $d->voyage->id]) }}#vente"
                                       class="btn btn-sm {{ $actif ? 'btn-primary' : 'btn-soft' }}" data-sa-row-link="edit">Choisir</a>
                                @else
                                    <span class="sa-sub">Complet</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-admin.card>

{{-- 2. seats, traveller, payment --}}
@if ($choix)
    @php $total = (int) ($choix->voyage->autocar?->nbr_siege ?? 0); @endphp
    <form method="POST" action="{{ route('reservation.admin.guichet.vendre') }}" id="vente" class="row g-3" data-prix="{{ $choix->prix }}">
        @csrf
        <input type="hidden" name="voyage_id" value="{{ $choix->voyage->id }}">
        <input type="hidden" name="arret_depart_id" value="{{ $choix->depart->id }}">
        <input type="hidden" name="arret_arrivee_id" value="{{ $choix->arrivee->id }}">

        <div class="col-xl-7">
            <x-admin.card title="Sièges" :subtitle="'Jusqu\'à ' . config('safar.max_sieges') . ' sièges. 1 et 4 = fenêtre.'" icon="bi-grid-3x3-gap">
                @error('seats')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                <div class="sa-seats" data-max="{{ config('safar.max_sieges') }}">
                    <div class="sa-seats-front"><i class="bi bi-person-badge"></i> Conducteur · porte</div>
                    @for ($n = 1; $n <= $total; $n++)
                        @if (($n - 1) % 4 === 2)<span class="sa-seat-aisle"></span>@endif
                        @php $occupe = in_array($n, $pris, true); @endphp
                        <label class="sa-seat {{ $occupe ? 'is-taken' : '' }}" title="{{ $occupe ? 'Occupé' : 'Siège ' . $n }}">
                            <input type="checkbox" name="seats[]" value="{{ $n }}" @disabled($occupe) @checked(in_array($n, (array) old('seats', [])))>
                            <span>{{ $n }}</span>
                        </label>
                    @endfor
                </div>
                <div class="d-flex flex-wrap gap-3 mt-3 sa-sub">
                    <span><span class="sa-seat-dot"></span> Libre</span>
                    <span><span class="sa-seat-dot is-selected"></span> Choisi</span>
                    <span><span class="sa-seat-dot is-taken"></span> Occupé</span>
                </div>
            </x-admin.card>
        </div>

        <div class="col-xl-5">
            <div class="sa-gap">
                <x-admin.card title="Voyageur" subtitle="Seul le nom est obligatoire. Téléphone ou e-mail : pour lui envoyer le billet." icon="bi-person">
                    <div class="row g-3">
                        <x-admin.field name="nom" label="Nom complet" col="col-12" required />
                        <x-admin.field name="telephone" label="Téléphone" col="col-md-6" icon="bi-telephone" placeholder="06 12 34 56 78" hint="Facultatif : pour WhatsApp." />
                        <x-admin.field type="email" name="email" label="E-mail" col="col-md-6" icon="bi-envelope" hint="Facultatif : pour lui envoyer le billet." />
                    </div>
                </x-admin.card>

                <x-admin.card title="Encaissement" subtitle="Payé maintenant : le billet sort payé, jamais « à payer plus tard »." icon="bi-cash-coin">
                    <div class="d-flex gap-2 mb-3">
                        @foreach (\App\Models\Encaissement::MODES as $cle => $label)
                            <input type="radio" class="btn-check" name="mode" id="mode-{{ $cle }}" value="{{ $cle }}" @checked(old('mode', 'especes') === $cle)>
                            <label class="btn btn-outline-primary flex-fill" for="mode-{{ $cle }}"><i class="bi {{ $cle === 'carte' ? 'bi-credit-card' : 'bi-cash' }}"></i> {{ $label }}</label>
                        @endforeach
                    </div>
                    <div class="sa-sub mb-3"><i class="bi bi-info-circle"></i> Carte : faites payer sur votre terminal (TPE), puis validez ici. Rien d'autre ne s'ouvre, comme pour les espèces.</div>
                    <div class="d-flex justify-content-between align-items-baseline mb-3">
                        <span class="sa-sub"><span data-nb>0</span> siège(s) × {{ number_format($choix->prix, 2, ',', ' ') }} DH</span>
                        <span class="sa-stat-value" data-total>0,00 DH</span>
                    </div>
                    <button class="btn btn-primary btn-lg w-100" data-vendre disabled><i class="bi bi-check2-circle"></i> Encaisser et émettre les billets</button>
                </x-admin.card>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        (function () {
            var form = document.getElementById('vente');
            var prix = parseFloat(form.dataset.prix);
            var max = parseInt(form.querySelector('.sa-seats').dataset.max, 10);
            function maj() {
                var coches = form.querySelectorAll('input[name="seats[]"]:checked');
                form.querySelector('[data-nb]').textContent = coches.length;
                form.querySelector('[data-total]').textContent = (coches.length * prix).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DH';
                form.querySelector('[data-vendre]').disabled = coches.length === 0;
                form.querySelectorAll('input[name="seats[]"]:not(:checked):not([disabled])').forEach(function (cb) {
                    cb.closest('.sa-seat').classList.toggle('is-full', coches.length >= max);
                    cb.disabled = false;
                });
            }
            form.addEventListener('change', function (e) {
                if (e.target.name === 'seats[]' && form.querySelectorAll('input[name="seats[]"]:checked').length > max) {
                    e.target.checked = false;
                }
                maj();
            });
            form.addEventListener('submit', function () { form.querySelector('[data-vendre]').disabled = true; }); // no double sale
            maj();
        })();
    </script>
    @endpush
@endif
@endsection

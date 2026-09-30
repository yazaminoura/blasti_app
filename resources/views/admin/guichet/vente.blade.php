@extends('admin.Layout.app')
@section('title', 'Vente ' . $commande)

@section('content')
@php
    $premier = $billets->first();
    $d = $premier->departAt();
    $email = $client && ! str_ends_with($client->email, '.invalid') ? $client->email : null;
    $encaisse = $billets->flatMap->encaissements;
@endphp

<x-admin.page-header :title="'Vente ' . $commande"
    :subtitle="$premier->villeDepart?->ville . ' → ' . $premier->villeArrivee?->ville . ' · ' . ucfirst($d->translatedFormat('l d F')) . ' à ' . $d->format('H:i')"
    :back="route('reservation.admin.guichet')" backLabel="Guichet">
    <a href="{{ route('reservation.admin.guichet') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Client suivant</a>
</x-admin.page-header>

<div class="row g-4">
    <div class="col-xl-5">
        <x-admin.card title="Remettre les billets" icon="bi-send">
            <div class="d-grid gap-2">
                <a href="{{ route('reservation.admin.guichet.imprimer', $commande) }}" target="_blank" class="btn btn-primary btn-lg"><i class="bi bi-printer"></i> Imprimer le ticket (imprimante à ticket)</a>
                <a href="{{ route('reservation.admin.guichet.pdf', $commande) }}" target="_blank" class="btn btn-soft"><i class="bi bi-file-earmark-pdf"></i> Billets en PDF (feuille A5 / A4)</a>
                <button type="button" class="btn btn-soft btn-lg" data-partager
                        data-pdf="{{ route('reservation.admin.guichet.pdf', $commande) }}" data-nom="billets-{{ $commande }}.pdf"
                        data-texte="{{ $texte }}" data-wa="{{ $whatsapp }}">
                    <i class="bi bi-whatsapp"></i> WhatsApp avec le PDF{{ $client?->telephone ? ' (' . $client->telephone . ')' : '' }}
                </button>
                <div class="sa-sub text-center" data-partager-aide hidden>
                    Le PDF vient d'être téléchargé : joignez-le dans la conversation WhatsApp qui s'ouvre (trombone 📎).
                </div>
                <div class="sa-sub text-center">
                    @if ($email)
                        <i class="bi bi-envelope-check"></i> Billets envoyés par e-mail à {{ $email }}.
                    @else
                        Pas d'e-mail : imprimez ou envoyez sur WhatsApp. Le voyageur peut aussi montrer le QR code sur son téléphone.
                    @endif
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Encaissé" icon="bi-cash-coin" class="mt-3">
            <dl class="sa-dl">
                <dt>Montant</dt><dd class="sa-strong">{{ number_format($encaisse->sum('montant'), 2, ',', ' ') }} DH</dd>
                <dt>Mode</dt><dd>{{ \App\Models\Encaissement::MODES[$encaisse->first()?->mode] ?? '—' }}</dd>
                <dt>Par</dt><dd>{{ $encaisse->first()?->user?->name ?? '—' }}</dd>
                <dt>Voyageur</dt><dd>{{ $client?->name }}<div class="sa-sub">{{ $client?->telephone }}</div></dd>
            </dl>
        </x-admin.card>
    </div>

    <div class="col-xl-7">
        <x-admin.card title="Billets" icon="bi-ticket-perforated" flush>
            <div class="sa-table-wrap">
                <table class="table sa-table align-middle">
                    <thead><tr><th>Billet</th><th>Siège</th><th>Passager</th><th>Compagnie · bus</th><th class="text-end">Prix</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($billets as $b)
                            <tr>
                                <td class="sa-num sa-strong">#{{ $b->id }}</td>
                                <td><span class="sa-chip brand">N° {{ $b->num_siege }}</span></td>
                                <td style="min-width: 220px;">
                                    @if (! $b->isCancelled() && $b->departAt()->isFuture() && auth()->user()->hasPermission('reservations.update'))
                                        {{-- typo at the counter: fix the name, same ticket and QR code --}}
                                        <form method="POST" action="{{ route('reservation.admin.guichet.passager', $b) }}" class="d-flex gap-1">
                                            @csrf
                                            @method('PATCH')
                                            <input name="passager_nom" value="{{ $b->passager() }}" class="form-control form-control-sm" maxlength="120" required aria-label="Passager du siège {{ $b->num_siege }}">
                                            <button class="sa-icon-btn" title="Corriger le nom"><i class="bi bi-check2"></i></button>
                                        </form>
                                    @else
                                        {{ $b->passager() }}
                                    @endif
                                </td>
                                <td>{{ $b->autocar?->societe?->raison_social }}<div class="sa-sub">{{ $b->autocar?->matricule }}</div></td>
                                <td class="text-end sa-num">{{ number_format($b->total(), 2, ',', ' ') }} DH</td>
                                <td class="text-end"><x-admin.row-actions :show="route('reservation.admin.show', $b->id)" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    </div>
</div>
@push('scripts')
<script>
    // WhatsApp with the PDF file: the share sheet of the phone / tablet / Windows (WhatsApp is in it).
    // A browser that cannot share files: the PDF is downloaded and WhatsApp opens with the message + ticket links.
    document.querySelector('[data-partager]').addEventListener('click', async function () {
        var btn = this;
        btn.disabled = true;
        try {
            var blob = await (await fetch(btn.dataset.pdf, { credentials: 'same-origin' })).blob();
            var fichier = new File([blob], btn.dataset.nom, { type: 'application/pdf' });
            if (navigator.canShare && navigator.canShare({ files: [fichier] })) {
                await navigator.share({ files: [fichier], text: btn.dataset.texte });
                return;
            }
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = btn.dataset.nom;
            a.click();
            document.querySelector('[data-partager-aide]').hidden = false;
            window.open(btn.dataset.wa, '_blank', 'noopener');
        } catch (e) {
            if (e.name !== 'AbortError') window.open(btn.dataset.wa, '_blank', 'noopener');
        } finally {
            btn.disabled = false;
        }
    });
</script>
@endpush
@endsection

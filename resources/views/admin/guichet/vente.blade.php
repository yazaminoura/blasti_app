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
    @if ($corrigeable)
        <form method="POST" action="{{ route('reservation.admin.guichet.annuler', $commande) }}" onsubmit="confirmDelete(event, this)"
              data-confirm="Annuler toute la vente ?" data-confirm-text="Les {{ count($corrigeable) }} billet(s) sont annulés, les sièges libérés. Vous rendez {{ number_format($actifs->whereIn('id', $corrigeable)->sum(fn ($b) => $b->encaissements->sum('montant')), 2, ',', ' ') }} DH au voyageur." data-confirm-button="Oui, annuler la vente">
            @csrf
            @method('DELETE')
            <button class="btn btn-soft text-danger"><i class="bi bi-x-circle"></i> Annuler la vente</button>
        </form>
    @endif
    <a href="{{ route('reservation.admin.guichet') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Client suivant</a>
</x-admin.page-header>

@if ($corrigeable && $correctionJusqua && ! auth()->user()->hasPermission('reservations.rembourser') && ! auth()->user()->isSuperAdmin())
    <div class="alert d-flex align-items-center gap-2 border-0 mb-3" style="background: var(--sa-info-soft); color: var(--sa-info);">
        <i class="bi bi-pencil-square"></i>
        Une erreur ? Siège, mode de paiement, billet en trop ou mauvais bus : corrigez ici jusqu'à {{ $correctionJusqua->format('H:i') }}.
    </div>
@elseif ($actifs->isEmpty())
    <div class="alert d-flex align-items-center gap-2 border-0 mb-3" style="background: var(--sa-danger-soft); color: var(--sa-danger);">
        <i class="bi bi-x-circle"></i> Vente annulée : tous les billets ont été retirés et remboursés.
    </div>
@endif

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
                <dt>Mode</dt>
                <dd>
                    @php $modeActuel = $encaisse->firstWhere('montant', '>', 0)?->mode; @endphp
                    @if ($corrigeable)
                        {{-- wrong button pressed at the counter: cash <-> card --}}
                        <form method="POST" action="{{ route('reservation.admin.guichet.mode', $commande) }}" class="d-flex gap-1"
                              data-bl-confirm="Changer le mode de paiement ?" data-bl-confirm-text="La caisse est corrigée pour toute la vente." data-bl-confirm-button="Oui, changer" data-bl-glyph="cash" data-bl-reset>
                            @csrf
                            @method('PATCH')
                            <select name="mode" class="form-select form-select-sm" onchange="this.form.requestSubmit()" aria-label="Mode de paiement">
                                @foreach (\App\Models\Encaissement::MODES as $cle => $label)
                                    <option value="{{ $cle }}" @selected($modeActuel === $cle)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    @else
                        {{ \App\Models\Encaissement::MODES[$modeActuel] ?? '—' }}
                    @endif
                </dd>
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
                                <td>
                                    @if (in_array($b->id, $corrigeable, true) && isset($libres[$b->id]))
                                        {{-- wrong seat: move to a free one (same ticket, same QR code) --}}
                                        <form method="POST" action="{{ route('reservation.admin.guichet.siege', $b) }}"
                                              data-bl-confirm="Changer le siège du billet #{{ $b->id }} ?" data-bl-confirm-text="Même billet, même QR code. Réimprimez ou renvoyez le billet ensuite." data-bl-confirm-button="Oui, changer" data-bl-glyph="seat" data-bl-reset>
                                            @csrf
                                            @method('PATCH')
                                            <select name="num_siege" class="form-select form-select-sm" style="width: 92px;" onchange="this.form.requestSubmit()" aria-label="Siège du billet {{ $b->id }}">
                                                @foreach ($libres[$b->id] as $n)
                                                    <option value="{{ $n }}" @selected($n === (int) $b->num_siege)>N° {{ $n }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @elseif ($b->isCancelled())
                                        <span class="sa-chip muted">N° {{ $b->num_siege }}</span>
                                    @else
                                        <span class="sa-chip brand">N° {{ $b->num_siege }}</span>
                                    @endif
                                </td>
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
                                <td class="text-end">
                                    @if ($b->isCancelled())
                                        <span class="sa-chip danger">Retiré</span>
                                    @else
                                        <x-admin.row-actions :show="route('reservation.admin.show', $b->id)"
                                            :delete="in_array($b->id, $corrigeable, true) ? route('reservation.admin.guichet.retirer', $b) : null"
                                            deleteLabel="Retirer ce billet (erreur)" deleteIcon="bi-x-circle" confirmButton="Oui, retirer"
                                            :confirm="'Retirer le billet du siège ' . $b->num_siege . ' ? Rendez ' . number_format($b->encaissements->sum('montant'), 2, ',', ' ') . ' DH au voyageur.'" />
                                    @endif
                                </td>
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

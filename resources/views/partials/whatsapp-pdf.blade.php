{{--
    "Envoyer sur WhatsApp" WITH the PDF ticket(s): the phone's share window (WhatsApp is in it) gets the PDF file + the message.
    A browser that cannot share files: the PDF is downloaded and WhatsApp opens with the message, the client attaches it (📎).
    Params: $billets (Reservation or collection), $pdf (URL of the PDF), $nom (file name), $class, $label (optional)
--}}
@php
    $texteWa = \App\Support\WhatsApp::texte($billets);
    $lienWa = \App\Support\WhatsApp::lien($billets);
@endphp
<button type="button" class="{{ $class }}" data-wa-pdf data-pdf="{{ $pdf }}" data-nom="{{ $nom }}" data-texte="{{ $texteWa }}" data-wa="{{ $lienWa }}">
    <i class="fab fa-whatsapp me-1"></i> {{ $label ?? __('Envoyer sur WhatsApp') }}
</button>
@once
    <div class="small text-muted mt-2 w-100" data-wa-pdf-aide hidden>{{ __('Le PDF vient d\'être téléchargé : joignez-le dans la conversation WhatsApp qui s\'ouvre (trombone 📎).') }}</div>
    <script>
        document.addEventListener('click', async function (e) {
            var btn = e.target.closest('[data-wa-pdf]');
            if (!btn) return;
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
                var aide = document.querySelector('[data-wa-pdf-aide]');
                if (aide) aide.hidden = false;
                window.open(btn.dataset.wa, '_blank', 'noopener');
            } catch (err) {
                if (err.name !== 'AbortError') window.open(btn.dataset.wa, '_blank', 'noopener');
            } finally {
                btn.disabled = false;
            }
        });
    </script>
@endonce

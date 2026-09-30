@extends('admin.Layout.app')
@section('title', 'Scanner')

@php
    $dh = fn ($m) => \App\Support\Controle::dh((float) $m);
    $options = $voyages->mapWithKeys(fn ($v) => [$v->id => \Carbon\Carbon::parse($v->date_depart)->format('d/m') . ' ' . substr($v->heure_depart, 0, 5)
        . ' · ' . $v->villeDepart?->ville . ' → ' . $v->villeArrivee?->ville . ' · ' . ($v->autocar?->matricule ?? '—')]);
    $scanUrl = route('reservation.admin.scan');
    $modes = \App\Models\Encaissement::MODES;
@endphp

@section('content')
<x-admin.page-header title="Scanner les billets" subtitle="À la porte du bus : visez le QR code du billet (papier ou téléphone). Chaque scan est vérifié ici et enregistré ; encaissez et faites monter sans quitter la page." />

{{-- the bus being checked: turns on the "wrong bus" check and the on-board counter --}}
<x-admin.card class="mb-3">
    <form method="GET" action="{{ route('reservation.admin.scanner') }}" class="row g-2 align-items-end">
        <div class="col-md-8">
            <label class="form-label" for="voyage">Bus contrôlé</label>
            <select name="voyage" id="voyage" class="form-select" onchange="this.form.submit()">
                <option value="">Tous les départs (pas de contrôle du bus)</option>
                @foreach ($options as $id => $label)
                    <option value="{{ $id }}" @selected($bus?->id === $id)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            @if ($bus)
                <a href="{{ route('voyages.passagers', $bus) }}" class="btn btn-primary w-100"><i class="bi bi-people"></i> Passagers du bus</a>
            @else
                <div class="sa-sub">Choisissez le bus pour refuser les billets d'un autre départ.</div>
            @endif
        </div>
    </form>
</x-admin.card>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <x-admin.stat label="À bord" :value="$bus ? $stats['bus']['a_bord'] . ' / ' . $stats['bus']['billets'] : '—'" icon="bi-bus-front" tone="success">
            {{ $bus ? 'voyageurs montés / billets vendus' : 'choisissez un bus' }}
        </x-admin.stat>
    </div>
    <div class="col-md-4">
        <x-admin.stat label="Reste à encaisser (ce bus)" :value="$bus ? $dh($stats['bus']['a_encaisser']) . ' DH' : '—'" icon="bi-hourglass-split" tone="warning">
            billets payables à la montée
        </x-admin.stat>
    </div>
    <div class="col-md-4">
        <x-admin.stat label="Ma caisse aujourd'hui" :value="$dh($stats['caisse']['especes'] + $stats['caisse']['carte']) . ' DH'" icon="bi-cash-coin" tone="info">
            <span data-stat="caisse-note">{{ $dh($stats['caisse']['especes']) }} DH espèces · {{ $dh($stats['caisse']['carte']) }} DH carte · {{ $stats['caisse']['n'] }} encaissement(s)</span>
        </x-admin.stat>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <x-admin.card>
            <div id="scanner" class="sa-scanner"></div>
            <div id="scanner-state" class="sa-sub text-center mt-2">Autorisez l'accès à la caméra quand le navigateur le demande.</div>
            <form id="manual" class="d-flex gap-2 mt-3">
                <input type="number" id="manual-number" min="1" class="form-control" placeholder="Sans caméra : n° du billet (ex : 125)" required>
                <button class="btn btn-primary"><i class="bi bi-search"></i> Vérifier</button>
            </form>
            <div class="form-check form-switch mt-3">
                <input class="form-check-input" type="checkbox" id="auto-board">
                <label class="form-check-label" for="auto-board">Faire monter automatiquement les billets valables et payés</label>
            </div>
        </x-admin.card>
    </div>
    <div class="col-lg-6">
        <div id="result" class="sa-card sa-scan-result is-idle" aria-live="polite">
            <div class="sa-scan-head">
                <span class="sa-scan-icon"><i class="bi bi-qr-code-scan"></i></span>
                <div>
                    <div class="sa-scan-title">En attente d'un billet</div>
                    <div class="sa-scan-text">Le résultat du scan s'affiche ici.</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- html5-qrcode reads the camera in the browser; the server checks the signed link of the ticket (App\Support\Controle) --}}
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const SCAN_URL = @json($scanUrl);
    const BUS = @json($bus?->id);
    const MODES = @json($modes);
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const state = document.getElementById('scanner-state');
    const result = document.getElementById('result');
    const auto = document.getElementById('auto-board');
    let reader = null, busy = false, lastCode = '', lastAt = 0, resumeTimer = null;

    try { auto.checked = localStorage.getItem('bl-scan-auto') === '1'; } catch (e) {}
    auto.addEventListener('change', () => { try { localStorage.setItem('bl-scan-auto', auto.checked ? '1' : '0'); } catch (e) {} });

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const dh = (n) => Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DH';

    // short beep + vibration: the controller does not have to look at the screen for a valid ticket
    function signal(ok) {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const o = ctx.createOscillator(), g = ctx.createGain();
            o.frequency.value = ok ? 880 : 220; o.type = ok ? 'sine' : 'square';
            g.gain.value = .08; o.connect(g); g.connect(ctx.destination);
            o.start(); o.stop(ctx.currentTime + (ok ? .15 : .45));
        } catch (e) {}
        if (navigator.vibrate) navigator.vibrate(ok ? 80 : [120, 80, 120]);
    }

    function pause() { try { reader && reader.pause(true); } catch (e) {} }
    function resume() {
        clearTimeout(resumeTimer);
        busy = false;
        try { reader && reader.resume(); } catch (e) {}
        state.textContent = 'Visez le QR code du billet suivant.';
    }

    async function post(url, body, method) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify(Object.assign({ _method: method || 'POST' }, body)),
        });
        if (res.status === 419) { location.reload(); throw new Error('session'); }
        return res.json();
    }

    function updateStats(stats) {
        if (!stats) return;
        const values = document.querySelectorAll('.sa-stat-value');
        if (stats.bus) {
            values[0].textContent = stats.bus.a_bord + ' / ' + stats.bus.billets;
            values[1].textContent = dh(stats.bus.a_encaisser);
        }
        values[2].textContent = dh(stats.caisse.especes + stats.caisse.carte);
        document.querySelector('[data-stat="caisse-note"]').textContent =
            dh(stats.caisse.especes) + ' espèces · ' + dh(stats.caisse.carte) + ' carte · ' + stats.caisse.n + ' encaissement(s)';
    }

    function render(data, done) {
        const v = data.verdict, b = data.billet;
        const icon = done ? 'bi-check2-circle' : { ok: 'bi-check-circle', payer: 'bi-cash-coin', stop: 'bi-x-octagon' }[v.ton];
        let html = '<div class="sa-scan-head"><span class="sa-scan-icon"><i class="bi ' + icon + '"></i></span><div>'
            + '<div class="sa-scan-title">' + esc(done ? 'Voyageur à bord' : v.titre) + '</div>'
            + '<div class="sa-scan-text">' + esc(done ? data.message : v.texte) + '</div></div></div>';

        if (b) {
            html += '<dl class="sa-scan-ticket">'
                + '<div><dt>Billet</dt><dd><a href="' + esc(b.lien) + '" target="_blank">N° ' + esc(b.id) + '</a></dd></div>'
                + '<div><dt>Passager</dt><dd>' + esc(b.passager) + '</dd></div>'
                + '<div><dt>Trajet</dt><dd>' + esc(b.trajet) + '</dd></div>'
                + '<div><dt>Départ</dt><dd>' + esc(b.depart) + '</dd></div>'
                + '<div><dt>Siège</dt><dd class="sa-scan-seat">' + esc(b.siege) + '</dd></div>'
                + '<div><dt>Autocar</dt><dd>' + esc(b.autocar || '—') + '</dd></div></dl>';
        }

        html += '<div class="sa-scan-actions">';
        if (!done && v.ton === 'payer') {
            Object.keys(MODES).forEach((mode, i) => {
                html += '<button class="btn ' + (i ? 'btn-light' : 'btn-warning') + ' btn-lg" data-board="' + mode + '">'
                    + '<i class="bi ' + (mode === 'carte' ? 'bi-credit-card' : 'bi-cash') + '"></i> ' + esc(MODES[mode]) + ' ' + dh(b.reste) + ' · faire monter</button>';
            });
        } else if (!done && v.ton === 'ok') {
            html += '<button class="btn btn-success btn-lg" data-board=""><i class="bi bi-box-arrow-in-right"></i> Faire monter</button>';
        }
        html += '<button class="btn btn-light" data-next><i class="bi bi-qr-code-scan"></i> Billet suivant</button></div>';

        result.className = 'sa-card sa-scan-result is-' + (done ? 'ok' : v.ton);
        result.innerHTML = html;
        updateStats(data.stats);

        result.querySelectorAll('[data-board]').forEach((btn) => btn.addEventListener('click', () => board(data.urls.embarquer, btn.dataset.board, btn)));
        result.querySelector('[data-next]').addEventListener('click', resume);
    }

    async function board(url, mode, btn) {
        result.querySelectorAll('button').forEach((x) => x.disabled = true);
        if (btn) btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Un instant…';
        try {
            const data = await post(url, { mode: mode || null, voyage: BUS }, 'PATCH');
            if (data.erreur) { data.verdict = Object.assign({}, data.verdict, { ton: 'stop', texte: data.erreur }); render(data, false); signal(false); return; }
            render(data, true);
            signal(true);
            resumeTimer = setTimeout(resume, 2500);
        } catch (e) {
            if (e.message !== 'session') { state.textContent = 'Connexion perdue : réessayez.'; result.querySelectorAll('button').forEach((x) => x.disabled = false); }
        }
    }

    async function check(code) {
        // the camera sees the same QR code many times per second
        if (busy || (code === lastCode && Date.now() - lastAt < 4000)) return;
        busy = true; lastCode = code; lastAt = Date.now();
        pause();
        state.textContent = 'Vérification…';
        try {
            const data = await post(SCAN_URL, { code: code, voyage: BUS });
            if (data.verdict.ton === 'ok' && auto.checked && data.urls) { return board(data.urls.embarquer, '', null); }
            render(data, false);
            signal(data.verdict.ton === 'ok');
            state.textContent = data.verdict.ton === 'stop' ? 'Billet refusé.' : 'Billet lu.';
        } catch (e) {
            if (e.message !== 'session') { state.textContent = 'Connexion perdue : réessayez.'; busy = false; try { reader && reader.resume(); } catch (x) {} }
        }
    }

    document.getElementById('manual').addEventListener('submit', (e) => {
        e.preventDefault();
        const input = document.getElementById('manual-number');
        lastCode = ''; busy = false;
        check(input.value.trim());
        input.value = '';
    });

    if (!window.Html5Qrcode) { state.textContent = 'Scanner indisponible : utilisez le numéro du billet.'; return; }
    reader = new Html5Qrcode('scanner');
    reader.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 250, height: 250 } }, check)
        .then(() => { state.textContent = 'Visez le QR code du billet.'; })
        .catch(() => { reader = null; state.textContent = 'Caméra inaccessible (autorisation refusée ou page non sécurisée en https). Utilisez le numéro du billet.'; });
})();
</script>
@endsection

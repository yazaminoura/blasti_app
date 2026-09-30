/*
 * One look for every popup and notification of the site AND the admin (SweetAlert2 underneath, styled in
 * assets/css/blasti-popups.css). Icons are drawn inline, so they work with or without an icon font.
 *
 *   BlastiAlert.fire({ type, title, text, html, icon, confirmText, cancelText, danger, links: [{ label, href, icon, newTab, primary }] })
 *       type: info | success | warning | error | question — returns the SweetAlert promise
 *       icon: optional icon-font class replacing the drawn icon (e.g. 'isax-sms-tracking5')
 *       glyph: optional drawn icon instead of the type's one: danger (trash) | logout | lock | unlock
 *   BlastiAlert.toast(type, message, title)   small card in the corner that disappears by itself
 *   BlastiAlert.confirm(title, text, { confirmText, danger }) -> Promise<boolean>
 *
 *   <form data-bl-confirm="Question ?" data-bl-confirm-text="..." data-bl-confirm-button="Oui" data-bl-danger data-bl-glyph="cash" data-bl-reset>
 *       (the same attributes on a submit button apply to that button only; data-bl-reset: Annuler resets the form)
 *       asks before submitting (replaces the browser's grey confirm() box)
 */
(function () {
    'use strict';

    const svg = (d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + d + '</svg>';
    const ICONS = {
        success: svg('<path d="M20 6 9 17l-5-5"/>'),
        error: svg('<path d="M18 6 6 18M6 6l12 12"/>'),
        warning: svg('<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>'),
        info: svg('<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>'),
        question: svg('<circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01"/>'),
        danger: svg('<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/>'),
        logout: svg('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>'),
        lock: svg('<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'),
        unlock: svg('<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 7.9-1"/>'),
        cash: svg('<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>'),
        refund: svg('<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>'),
        board: svg('<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>'),
        eye: svg('<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>'),
        eyeoff: svg('<path d="M3 3l18 18M10.6 5.1A10.9 10.9 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.2 4.2M6.6 6.6A18 18 0 0 0 2 12s3.5 7 10 7a10 10 0 0 0 5.4-1.6M9.9 9.9a3 3 0 0 0 4.2 4.2"/>'),
        seat: svg('<path d="M6 19v2M18 19v2M5 11V6a3 3 0 0 1 3-3h8a3 3 0 0 1 3 3v5"/><path d="M3 11h18v5a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3z"/>'),
        calendar: svg('<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M9 15h6"/>'),
        ticket: svg('<path d="M3 8a2 2 0 0 0 0 4v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0 0-4V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z"/><path d="M13 5v2M13 11v2M13 17v1"/>'),
    };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const lang = (document.documentElement.lang || 'fr').slice(0, 2);
    const T = {
        ok: { fr: 'Compris', en: 'OK', ar: 'حسنًا' },
        cancel: { fr: 'Annuler', en: 'Cancel', ar: 'إلغاء' },
        yes: { fr: 'Oui, continuer', en: 'Yes, continue', ar: 'نعم، متابعة' },
    };
    const t = (k) => (T[k][lang] || T[k].fr);

    function fire(opts) {
        const o = Object.assign({ type: 'info' }, opts || {});
        if (!window.Swal) {
            alert([o.title, o.text].filter(Boolean).join('\n\n'));
            return Promise.resolve({ isConfirmed: true });
        }
        const tone = o.danger ? 'danger' : o.type;

        let html = o.html || (o.text ? '<p class="bp-text">' + esc(o.text) + '</p>' : '');
        if (o.links && o.links.length) {
            html += '<div class="bp-links">' + o.links.map((l) =>
                '<a href="' + esc(l.href) + '" class="bp-btn bp-btn--' + (l.primary ? 'primary' : 'soft') + '"'
                + (l.newTab ? ' target="_blank" rel="noopener"' : '') + '>'
                + (l.icon ? '<i class="isax ' + esc(l.icon) + '"></i>' : '') + esc(l.label) + '</a>').join('') + '</div>';
        }

        return Swal.fire({
            title: o.title || '',
            html: html,
            iconHtml: o.icon ? '<i class="' + esc(o.icon.startsWith('isax-') ? 'isax ' + o.icon : o.icon) + '"></i>' : (ICONS[o.glyph] || ICONS[tone] || ICONS.info),
            icon: o.type === 'question' ? 'question' : (o.type in ICONS ? o.type : 'info'),
            showCancelButton: !!o.cancelText,
            confirmButtonText: o.confirmText || t('ok'),
            cancelButtonText: o.cancelText || t('cancel'),
            showConfirmButton: o.showConfirm !== false,
            reverseButtons: true,
            focusCancel: !!o.danger,
            buttonsStyling: false,
            showClass: { popup: 'bp-in' },
            hideClass: { popup: 'bp-out' },
            customClass: {
                container: 'bp-backdrop',
                popup: 'bp-modal bp-modal--' + tone,
                icon: 'bp-icon',
                title: 'bp-title',
                htmlContainer: 'bp-body',
                actions: 'bp-actions',
                confirmButton: 'bp-btn bp-btn--' + (o.danger ? 'danger' : 'primary'),
                cancelButton: 'bp-btn bp-btn--light',
            },
        });
    }

    function confirm(title, text, opts) {
        const o = opts || {};
        return fire({ type: 'warning', title: title, text: text, confirmText: o.confirmText || t('yes'), cancelText: t('cancel'), danger: o.danger, glyph: o.glyph || (o.danger ? 'danger' : null) })
            .then((r) => !!r.isConfirmed);
    }

    function toast(type, message, title) {
        if (!window.Swal) return;
        type = type in ICONS ? type : 'info';
        Swal.fire({
            toast: true,
            position: document.documentElement.dir === 'rtl' ? 'bottom-start' : 'bottom-end',
            iconHtml: ICONS[type],
            icon: type,
            title: title ? esc(title) : '',
            html: '<span class="bp-toast-text">' + esc(message) + '</span>',
            showConfirmButton: false,
            showCloseButton: true,
            timer: 5000,
            timerProgressBar: true,
            customClass: { popup: 'bp-toast bp-toast--' + type, icon: 'bp-toast-icon', title: 'bp-toast-title', htmlContainer: 'bp-toast-body', closeButton: 'bp-toast-close' },
            didOpen: (el) => { el.addEventListener('mouseenter', Swal.stopTimer); el.addEventListener('mouseleave', Swal.resumeTimer); },
        });
    }

    // <form data-bl-confirm="..."> asks first
    document.addEventListener('submit', function (e) {
        const form = e.target;
        // the question can sit on the clicked button (forms with several buttons) or on the form
        const btn = e.submitter && e.submitter.dataset && e.submitter.dataset.blConfirm ? e.submitter : null;
        const src = btn || form;
        if (!src.dataset || !src.dataset.blConfirm || form.dataset.blOk) return;
        e.preventDefault();
        confirm(src.dataset.blConfirm, src.dataset.blConfirmText || '', {
            confirmText: src.dataset.blConfirmButton, danger: 'blDanger' in src.dataset, glyph: src.dataset.blGlyph,
        }).then(function (oui) {
            if (!oui) {
                // "Annuler": a select that submitted its form goes back to its old value
                if ('blReset' in form.dataset) form.reset();
                return;
            }
            // keep the value of the clicked button (e.g. name="mode" value="carte")
            if (e.submitter && e.submitter.name) {
                const champ = document.createElement('input');
                champ.type = 'hidden';
                champ.name = e.submitter.name;
                champ.value = e.submitter.value;
                form.appendChild(champ);
            }
            form.dataset.blOk = '1';
            form.submit();
        });
    }, true);

    window.BlastiAlert = { fire: fire, toast: toast, confirm: confirm };
})();

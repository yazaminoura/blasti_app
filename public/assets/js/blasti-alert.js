/*
 * One look for every popup of the public site (SweetAlert2 underneath, styled in blasti.css "Popups").
 *
 *   BlastiAlert.fire({ type, title, text, html, icon, confirmText, cancelText, danger, links: [{ label, href, icon, newTab }] })
 *       icon: optional isax class replacing the icon of the type (e.g. 'isax-sms-tracking5' for e-mail messages)
 *       type: info | success | warning | error | question — returns the SweetAlert promise
 *   BlastiAlert.toast(type, message, title)   small message in the corner (flash messages)
 */
(function () {
    'use strict';

    const ICONS = {
        info: 'isax-info-circle5',
        success: 'isax-tick-circle5',
        warning: 'isax-warning-25',
        error: 'isax-close-circle5',
        question: 'isax-message-question5',
    };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const lang = document.documentElement.lang || 'fr';
    const T = {
        ok: { fr: 'Compris', en: 'OK', ar: 'حسنًا' },
        cancel: { fr: 'Annuler', en: 'Cancel', ar: 'إلغاء' },
    };
    const t = (k) => (T[k][lang.slice(0, 2)] || T[k].fr);

    function fire(opts) {
        const o = Object.assign({ type: 'info' }, opts || {});
        if (!window.Swal) {
            alert([o.title, o.text].filter(Boolean).join('\n\n'));
            return Promise.resolve({ isConfirmed: true });
        }

        let html = o.html || (o.text ? '<p class="bl-swal-text">' + esc(o.text) + '</p>' : '');
        if (o.links && o.links.length) {
            html += '<div class="bl-swal-links">' + o.links.map((l) =>
                '<a href="' + esc(l.href) + '" class="bl-swal-btn bl-swal-btn--' + (l.primary ? 'primary' : 'soft') + '"'
                + (l.newTab ? ' target="_blank" rel="noopener"' : '') + '>'
                + (l.icon ? '<i class="isax ' + esc(l.icon) + '"></i>' : '') + esc(l.label) + '</a>').join('') + '</div>';
        }

        return Swal.fire({
            title: o.title || '',
            html: html,
            iconHtml: '<i class="isax ' + esc(o.icon || ICONS[o.type] || ICONS.info) + '"></i>',
            icon: o.type === 'question' ? 'question' : o.type,
            showCancelButton: !!o.cancelText,
            confirmButtonText: o.confirmText || t('ok'),
            cancelButtonText: o.cancelText || t('cancel'),
            showConfirmButton: o.showConfirm !== false,
            reverseButtons: true,
            focusConfirm: true,
            buttonsStyling: false,
            showClass: { popup: 'bl-swal-in' },
            hideClass: { popup: 'bl-swal-out' },
            customClass: {
                popup: 'bl-swal bl-swal--' + o.type,
                icon: 'bl-swal-icon',
                title: 'bl-swal-title',
                htmlContainer: 'bl-swal-body',
                actions: 'bl-swal-actions',
                confirmButton: 'bl-swal-btn bl-swal-btn--' + (o.danger ? 'danger' : 'primary'),
                cancelButton: 'bl-swal-btn bl-swal-btn--light',
            },
        });
    }

    function toast(type, message, title) {
        if (!window.Swal) return;
        Swal.fire({
            toast: true,
            position: document.documentElement.dir === 'rtl' ? 'bottom-start' : 'bottom-end',
            iconHtml: '<i class="isax ' + (ICONS[type] || ICONS.info) + '"></i>',
            icon: type in ICONS ? type : 'info',
            title: title ? esc(title) : '',
            html: '<span class="bl-toast-text">' + esc(message) + '</span>',
            showConfirmButton: false,
            showCloseButton: true,
            timer: 5000,
            timerProgressBar: true,
            customClass: { popup: 'bl-toast bl-toast--' + type, icon: 'bl-toast-icon', title: 'bl-toast-title', htmlContainer: 'bl-toast-body', closeButton: 'bl-toast-close' },
            didOpen: (el) => { el.addEventListener('mouseenter', Swal.stopTimer); el.addEventListener('mouseleave', Swal.resumeTimer); },
        });
    }

    window.BlastiAlert = { fire: fire, toast: toast };
})();

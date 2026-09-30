/* Safar admin: theme switch, sidebar, upload previews, delete confirmations. */
(function () {
    var root = document.documentElement;
    var THEME_KEY = 'safar-admin-theme';
    var SIDEBAR_KEY = 'safar-admin-sidebar';
    var darkQuery = window.matchMedia('(prefers-color-scheme: dark)');

    function store(key, value) {
        try { localStorage.setItem(key, value); } catch (e) {}
    }

    // ---- Theme -------------------------------------------------------------
    function applyTheme(choice) {
        var dark = choice === 'dark' || (choice === 'auto' && darkQuery.matches);
        root.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        root.setAttribute('data-theme-choice', choice);
        document.querySelectorAll('[data-sa-theme]').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.saTheme === choice);
            btn.setAttribute('aria-pressed', btn.dataset.saTheme === choice ? 'true' : 'false');
        });
        document.dispatchEvent(new CustomEvent('sa:theme', { detail: { dark: dark } }));
    }

    darkQuery.addEventListener('change', function () {
        if (root.getAttribute('data-theme-choice') === 'auto') applyTheme('auto');
    });

    // ---- Sidebar -------------------------------------------------------------
    function openSidebar(open) {
        root.classList.toggle('sa-sidebar-open', open);
    }

    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-sa-theme]');
        if (t) {
            store(THEME_KEY, t.dataset.saTheme);
            applyTheme(t.dataset.saTheme);
            return;
        }
        if (e.target.closest('[data-sa-sidebar-open]')) return openSidebar(true);
        if (e.target.closest('[data-sa-sidebar-close]')) return openSidebar(false);
        if (e.target.closest('[data-sa-sidebar-collapse]')) {
            var collapsed = root.classList.toggle('sa-collapsed');
            store(SIDEBAR_KEY, collapsed ? 'collapsed' : 'expanded');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') openSidebar(false);
    });

    // ---- Clickable rows: a click on a list row opens its form (edit first, else detail) ----------
    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0) return;
        var row = e.target.closest('.sa-table tbody tr, [data-sa-row]');
        if (!row || e.target.closest('a, button, input, select, textarea, label, form, [data-sa-no-row]')) return;
        if (String(window.getSelection && window.getSelection()).length) return; // user is selecting text
        var link = row.querySelector('[data-sa-row-link="edit"]') || row.querySelector('[data-sa-row-link]');
        if (!link) return;
        if (e.ctrlKey || e.metaKey) window.open(link.href, '_blank');
        else window.location.href = link.href;
    });

    // ---- Image upload preview -----------------------------------------------------
    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input.matches || !input.matches('[data-sa-preview]')) return;
        var target = document.getElementById(input.dataset.saPreview);
        var file = input.files && input.files[0];
        if (!target || !file || !file.type.startsWith('image/')) return;
        var reader = new FileReader();
        reader.onload = function () {
            target.innerHTML = '';
            var img = document.createElement('img');
            img.src = reader.result;
            img.alt = '';
            target.appendChild(img);
        };
        reader.readAsDataURL(file);
    });

    // ---- Brand color read from CSS (charts) -------------------------------------------
    window.saColor = function (name) {
        return getComputedStyle(root).getPropertyValue(name).trim();
    };

    document.addEventListener('DOMContentLoaded', function () {
        applyTheme(root.getAttribute('data-theme-choice') || 'auto');
    });
})();

// Usage: <form onsubmit="confirmDelete(event, this)" data-confirm="Question ?" data-confirm-button="Oui">
function confirmDelete(event, form) {
    event.preventDefault();
    // same popup as the website (assets/js/blasti-alert.js + assets/css/blasti-popups.css)
    var bouton = form.dataset.confirmButton || 'Oui, supprimer';
    BlastiAlert.fire({
        type: 'warning',
        title: form.dataset.confirm || 'Supprimer cet élément ?',
        text: form.dataset.confirmText || 'Cette action est définitive.',
        confirmText: bouton,
        cancelText: 'Annuler',
        // a red button for what removes or closes something, the brand colour otherwise (réactiver...)
        danger: !/réactiver|reactiver|publier|afficher/i.test(bouton),
        // the icon says what happens
        glyph: /réactiver|reactiver/i.test(bouton) ? 'unlock'
            : /désactiver|desactiver/i.test(bouton) ? 'lock'
            : /déconnecter|deconnecter/i.test(bouton) ? 'logout'
            : /supprimer|retirer|annuler/i.test(bouton) ? 'danger' : null
    }).then(function (result) {
        if (result.isConfirmed) form.submit();
    });
}

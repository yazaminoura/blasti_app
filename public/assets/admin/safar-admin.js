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
    Swal.fire({
        title: form.dataset.confirm || 'Supprimer cet élément ?',
        text: form.dataset.confirmText || 'Cette action est définitive.',
        icon: 'warning',
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonText: form.dataset.confirmButton || 'Oui, supprimer',
        cancelButtonText: 'Annuler',
        customClass: { popup: 'sa-swal', confirmButton: 'btn btn-danger', cancelButton: 'btn btn-light border me-2' },
        buttonsStyling: false
    }).then(function (result) {
        if (result.isConfirmed) form.submit();
    });
}

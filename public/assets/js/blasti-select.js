/*
 * Styled dropdown for <select data-bl-select> (city pickers).
 * The real <select> stays in the form (hidden): its value is what is submitted, and every choice fires
 * a normal "change" event, so existing handlers (AJAX filters, onchange="this.form.submit()") keep working.
 * Options: data-bl-icon="isax-location" (icon), data-bl-search="Rechercher..." (search box placeholder).
 */
(function () {
    'use strict';

    const norm = (s) => (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

    function enhance(select) {
        if (select.dataset.blReady) return;
        select.dataset.blReady = '1';

        const wrap = document.createElement('div');
        wrap.className = 'bl-select';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.classList.add('bl-select-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        const icon = select.dataset.blIcon || 'isax-location';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'bl-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.innerHTML = '<i class="isax ' + icon + ' bl-select-icon"></i><span class="bl-select-value"></span><i class="isax isax-arrow-down-1 bl-select-caret"></i>';
        wrap.insertBefore(trigger, select);

        const panel = document.createElement('div');
        panel.className = 'bl-select-panel';
        panel.hidden = true;
        const withSearch = select.options.length > 6;
        if (withSearch) {
            panel.innerHTML = '<div class="bl-select-search"><i class="isax isax-search-normal-1"></i><input type="text" autocomplete="off"></div>';
            panel.querySelector('input').placeholder = select.dataset.blSearch || 'Rechercher...';
        }
        const list = document.createElement('ul');
        list.className = 'bl-select-list';
        list.setAttribute('role', 'listbox');
        panel.appendChild(list);
        const empty = document.createElement('div');
        empty.className = 'bl-select-empty';
        empty.textContent = select.dataset.blEmpty || '—';
        empty.hidden = true;
        panel.appendChild(empty);
        wrap.appendChild(panel);

        const search = panel.querySelector('input');
        let active = -1;

        // the labels of the form point to the hidden select: make them open the dropdown
        if (select.id) {
            document.querySelectorAll('label[for="' + select.id + '"]').forEach((label) => {
                label.addEventListener('click', (e) => { e.preventDefault(); trigger.focus(); open(); });
            });
        }

        function build() {
            list.innerHTML = '';
            Array.from(select.options).forEach((opt, i) => {
                const li = document.createElement('li');
                li.className = 'bl-select-option' + (opt.value === '' ? ' is-placeholder' : '') + (opt.selected ? ' is-selected' : '');
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', opt.selected ? 'true' : 'false');
                li.dataset.index = i;
                li.dataset.search = norm(opt.text);
                if (opt.disabled) li.classList.add('is-disabled');
                li.innerHTML = '<span></span><i class="isax isax-tick-circle5 bl-select-check"></i>';
                li.firstChild.textContent = opt.text;
                list.appendChild(li);
            });
        }

        function sync() {
            const opt = select.options[select.selectedIndex];
            const value = trigger.querySelector('.bl-select-value');
            value.textContent = opt ? opt.text : '';
            trigger.classList.toggle('is-empty', !opt || opt.value === '');
            list.querySelectorAll('.bl-select-option').forEach((li) => {
                const on = Number(li.dataset.index) === select.selectedIndex;
                li.classList.toggle('is-selected', on);
                li.setAttribute('aria-selected', on ? 'true' : 'false');
            });
        }

        function visible() {
            return Array.from(list.querySelectorAll('.bl-select-option:not([hidden]):not(.is-disabled)'));
        }

        function highlight(i) {
            const items = visible();
            items.forEach((li) => li.classList.remove('is-active'));
            if (!items.length) { active = -1; return; }
            active = Math.max(0, Math.min(i, items.length - 1));
            items[active].classList.add('is-active');
            items[active].scrollIntoView({ block: 'nearest' });
        }

        function filter(text) {
            const q = norm(text.trim());
            let shown = 0;
            list.querySelectorAll('.bl-select-option').forEach((li) => {
                const hit = !q || (li.dataset.search.includes(q) && !li.classList.contains('is-placeholder'));
                li.hidden = !hit;
                if (hit) shown++;
            });
            empty.hidden = shown > 0;
            highlight(0);
        }

        function open() {
            if (!panel.hidden || select.disabled) return;
            document.querySelectorAll('.bl-select.is-open').forEach((other) => other !== wrap && other.blClose && other.blClose());
            build();
            sync();
            panel.hidden = false;
            wrap.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            if (search) { search.value = ''; filter(''); search.focus(); }
            const items = visible();
            highlight(Math.max(0, items.findIndex((li) => li.classList.contains('is-selected'))));
        }

        function close(focusTrigger) {
            if (panel.hidden) return;
            panel.hidden = true;
            wrap.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            if (focusTrigger) trigger.focus();
        }
        wrap.blClose = () => close(false);

        function choose(li) {
            if (!li || li.classList.contains('is-disabled')) return;
            const index = Number(li.dataset.index);
            const changed = select.selectedIndex !== index;
            select.selectedIndex = index;
            sync();
            close(true);
            if (changed) select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        trigger.addEventListener('click', () => (panel.hidden ? open() : close(true)));
        list.addEventListener('mousedown', (e) => e.preventDefault()); // keep focus in the search box
        list.addEventListener('click', (e) => choose(e.target.closest('.bl-select-option')));
        list.addEventListener('mousemove', (e) => {
            const li = e.target.closest('.bl-select-option');
            if (li) highlight(visible().indexOf(li));
        });
        if (search) search.addEventListener('input', () => filter(search.value));

        function keys(e) {
            if (panel.hidden) {
                if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) { e.preventDefault(); open(); }
                return;
            }
            if (e.key === 'ArrowDown') { e.preventDefault(); highlight(active + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(active - 1); }
            else if (e.key === 'Enter') { e.preventDefault(); choose(visible()[active]); }
            else if (e.key === 'Escape') { e.preventDefault(); close(true); }
            else if (e.key === 'Tab') { close(false); }
        }
        trigger.addEventListener('keydown', keys);
        if (search) search.addEventListener('keydown', keys);

        document.addEventListener('mousedown', (e) => { if (!wrap.contains(e.target)) close(false); });
        // value changed by code (e.g. the swap button, .val() + change): refresh the label
        select.addEventListener('change', sync);
        select.addEventListener('bl:sync', sync);

        build();
        sync();
    }

    /*
     * <input type="date" data-bl-date>: same look as the city pickers, date written in words
     * ("ven. 2 oct. 2026"), opens our own calendar (min/max of the input respected, Monday first, keyboard).
     * The real input keeps the value (Y-m-d) and fires "change".
     * Texts: data-bl-placeholder, data-bl-today, data-bl-clear, data-bl-prev, data-bl-next.
     */
    function enhanceDate(input) {
        if (input.dataset.blReady) return;
        input.dataset.blReady = '1';

        const wrap = document.createElement('div');
        wrap.className = 'bl-select bl-date';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
        input.classList.add('bl-date-native');
        input.tabIndex = -1;

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'bl-select-trigger';
        trigger.innerHTML = '<i class="isax isax-calendar-1 bl-select-icon"></i><span class="bl-select-value"></span>';
        wrap.insertBefore(trigger, input);

        const clear = document.createElement('span');
        clear.className = 'bl-date-clear';
        clear.setAttribute('role', 'button');
        clear.setAttribute('aria-label', input.dataset.blClear || 'Effacer');
        clear.innerHTML = '<i class="isax isax-close-circle5"></i>';
        trigger.appendChild(clear);

        const lang = document.documentElement.lang || 'fr';
        const locale = lang === 'ar' ? 'ar-MA' : lang;
        const fmt = new Intl.DateTimeFormat(locale, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
        const fmtMonth = new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' });
        const fmtDay = new Intl.DateTimeFormat(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        // Monday first (Morocco); 2024-01-01 was a Monday
        const weekdays = Array.from({ length: 7 }, (_, i) => new Intl.DateTimeFormat(locale, { weekday: 'short' }).format(new Date(2024, 0, 1 + i)));

        const iso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        const parse = (s) => { if (!s) return null; const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
        const todayIso = iso(new Date());

        // ---- calendar panel ----
        const panel = document.createElement('div');
        panel.className = 'bl-select-panel bl-cal';
        panel.hidden = true;
        panel.setAttribute('role', 'dialog');
        panel.innerHTML =
            '<div class="bl-cal-head">' +
                '<button type="button" class="bl-cal-nav" data-step="-1"><i class="isax isax-arrow-left-2"></i></button>' +
                '<span class="bl-cal-title"></span>' +
                '<button type="button" class="bl-cal-nav" data-step="1"><i class="isax isax-arrow-right-3"></i></button>' +
            '</div>' +
            '<div class="bl-cal-grid bl-cal-weekdays">' + weekdays.map((w) => '<span>' + w + '</span>').join('') + '</div>' +
            '<div class="bl-cal-grid bl-cal-days" role="grid"></div>' +
            '<div class="bl-cal-foot">' +
                '<button type="button" class="bl-cal-today"></button>' +
                '<button type="button" class="bl-cal-clear"></button>' +
            '</div>';
        wrap.appendChild(panel);
        panel.querySelector('[data-step="-1"]').setAttribute('aria-label', input.dataset.blPrev || '‹');
        panel.querySelector('[data-step="1"]').setAttribute('aria-label', input.dataset.blNext || '›');
        panel.querySelector('.bl-cal-today').textContent = input.dataset.blToday || "Aujourd'hui";
        panel.querySelector('.bl-cal-clear').textContent = input.dataset.blClear || 'Effacer';
        const days = panel.querySelector('.bl-cal-days');

        let view = new Date();     // month shown
        let focusIso = null;       // day with the keyboard focus

        const allowed = (s) => (!input.min || s >= input.min) && (!input.max || s <= input.max);

        function render() {
            const y = view.getFullYear();
            const m = view.getMonth();
            panel.querySelector('.bl-cal-title').textContent = fmtMonth.format(new Date(y, m, 1));
            const lead = (new Date(y, m, 1).getDay() + 6) % 7; // blanks before the 1st (Monday first)
            const count = new Date(y, m + 1, 0).getDate();

            let html = '';
            for (let i = 0; i < lead; i++) html += '<span></span>';
            for (let d = 1; d <= count; d++) {
                const s = iso(new Date(y, m, d));
                const cls = ['bl-cal-day'];
                if (s === todayIso) cls.push('is-today');
                if (s === input.value) cls.push('is-selected');
                const off = !allowed(s);
                html += '<button type="button" class="' + cls.join(' ') + '" data-date="' + s + '"' + (off ? ' disabled' : '') +
                    ' tabindex="' + (s === focusIso ? '0' : '-1') + '" aria-label="' + fmtDay.format(new Date(y, m, d)) + '">' + d + '</button>';
            }
            days.innerHTML = html;

            // no going back before the first allowed month
            const prev = panel.querySelector('[data-step="-1"]');
            prev.disabled = !!input.min && iso(new Date(y, m, 0)) < input.min;
            const next = panel.querySelector('[data-step="1"]');
            next.disabled = !!input.max && iso(new Date(y, m + 1, 1)) > input.max;
            panel.querySelector('.bl-cal-today').disabled = !allowed(todayIso);
        }

        function moveMonth(step) {
            view = new Date(view.getFullYear(), view.getMonth() + step, 1);
            render();
        }

        function focusDay(s) {
            const d = parse(s);
            if (d.getMonth() !== view.getMonth() || d.getFullYear() !== view.getFullYear()) {
                view = new Date(d.getFullYear(), d.getMonth(), 1);
            }
            focusIso = s;
            render();
            const btn = days.querySelector('[data-date="' + s + '"]');
            if (btn) btn.focus();
        }

        function sync() {
            const value = trigger.querySelector('.bl-select-value');
            value.textContent = input.value ? fmt.format(parse(input.value)) : (input.dataset.blPlaceholder || '');
            trigger.classList.toggle('is-empty', !input.value);
        }

        function set(s) {
            const changed = input.value !== s;
            input.value = s;
            sync();
            close(true);
            if (changed) input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function open() {
            if (!panel.hidden) return;
            document.querySelectorAll('.bl-select.is-open').forEach((other) => other !== wrap && other.blClose && other.blClose());
            const start = input.value || (allowed(todayIso) ? todayIso : input.min || todayIso);
            view = parse(start);
            view = new Date(view.getFullYear(), view.getMonth(), 1);
            focusIso = start;
            render();
            panel.hidden = false;
            wrap.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            const btn = days.querySelector('[data-date="' + start + '"]:not([disabled])');
            if (btn) btn.focus();
        }

        function close(focusTrigger) {
            if (panel.hidden) return;
            panel.hidden = true;
            wrap.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            if (focusTrigger) trigger.focus();
        }
        wrap.blClose = () => close(false);

        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.addEventListener('click', (e) => {
            if (e.target.closest('.bl-date-clear')) {
                close(false);
                set('');
                return;
            }
            panel.hidden ? open() : close(true);
        });
        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); }
            if ((e.key === 'Delete' || e.key === 'Backspace') && input.value) { e.preventDefault(); set(''); }
        });

        panel.addEventListener('mousedown', (e) => { if (!e.target.closest('input')) e.preventDefault(); });
        panel.addEventListener('click', (e) => {
            const nav = e.target.closest('.bl-cal-nav');
            if (nav && !nav.disabled) return moveMonth(Number(nav.dataset.step));
            const day = e.target.closest('.bl-cal-day');
            if (day && !day.disabled) return set(day.dataset.date);
            if (e.target.closest('.bl-cal-today') && allowed(todayIso)) return set(todayIso);
            if (e.target.closest('.bl-cal-clear')) return set('');
        });
        panel.addEventListener('keydown', (e) => {
            const rtl = getComputedStyle(wrap).direction === 'rtl';
            const cur = parse(focusIso || input.value || todayIso);
            const shift = (n) => { const d = new Date(cur); d.setDate(d.getDate() + n); return iso(d); };
            const moves = { ArrowLeft: rtl ? 1 : -1, ArrowRight: rtl ? -1 : 1, ArrowUp: -7, ArrowDown: 7 };
            if (e.key in moves) { e.preventDefault(); focusDay(shift(moves[e.key])); }
            else if (e.key === 'PageUp' || e.key === 'PageDown') {
                e.preventDefault();
                const d = new Date(cur.getFullYear(), cur.getMonth() + (e.key === 'PageUp' ? -1 : 1), Math.min(cur.getDate(), 28));
                focusDay(iso(d));
            }
            else if (e.key === 'Escape') { e.preventDefault(); close(true); }
            else if (e.key === 'Tab') { close(false); }
        });
        document.addEventListener('mousedown', (e) => { if (!wrap.contains(e.target)) close(false); });

        if (input.id) {
            document.querySelectorAll('label[for="' + input.id + '"]').forEach((label) => {
                label.addEventListener('click', (e) => { e.preventDefault(); trigger.focus(); open(); });
            });
        }
        input.addEventListener('change', sync);
        input.addEventListener('input', sync);

        sync();
    }

    function init(root) {
        (root || document).querySelectorAll('select[data-bl-select]').forEach(enhance);
        (root || document).querySelectorAll('input[type="date"][data-bl-date]').forEach(enhanceDate);

        // <button data-bl-swap="idFrom,idTo">: departure <-> arrival
        (root || document).querySelectorAll('[data-bl-swap]').forEach((btn) => {
            if (btn.dataset.blReady) return;
            btn.dataset.blReady = '1';
            btn.addEventListener('click', () => {
                const [a, b] = btn.dataset.blSwap.split(',').map((id) => document.getElementById(id.trim()));
                if (!a || !b || a.value === b.value) return;
                const tmp = a.value;
                a.value = b.value;
                b.value = tmp;
                btn.classList.remove('is-spinning');
                void btn.offsetWidth; // restart the animation
                btn.classList.add('is-spinning');
                // one "change" only, so an AJAX filter listening on both fields runs once
                a.dispatchEvent(new Event('bl:sync'));
                b.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    }

    window.BlastiSelect = { init: init, enhance: enhance };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => init());
    else init();
})();

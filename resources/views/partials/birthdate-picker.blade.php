{{--
    Custom date-of-birth picker (month + year dropdowns, so nobody has to click back 30 years).
    Submits an ISO date (YYYY-MM-DD) in a hidden input, so validation is unchanged.
    Usage: @include('partials.birthdate-picker', ['name' => 'birthdate', 'value' => old('birthdate'), 'max' => now()->subDay()->format('Y-m-d')])
--}}
@php
    $name = $name ?? 'birthdate';
    $id = $id ?? $name;
    $value = $value ?? null;
    $max = $max ?? now()->subDay()->format('Y-m-d');
    $min = $min ?? now()->subYears(120)->format('Y-m-d');
@endphp

<div class="dp" id="dp-{{ $id }}" data-min="{{ $min }}" data-max="{{ $max }}">
    <input type="hidden" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}">
    <button type="button" class="dp-input" aria-haspopup="dialog" aria-expanded="false" aria-label="Choose birthdate">
        <span class="dp-text">Select your birthdate</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="3"/><path d="M3.5 10h17M8 3v4M16 3v4"/></svg>
    </button>

    <div class="dp-pop" role="dialog" aria-label="Choose birthdate" hidden>
        <div class="dp-head">
            <button type="button" class="dp-nav" data-nav="-1" aria-label="Previous month">&#8249;</button>
            <select class="dp-month" aria-label="Month"></select>
            <select class="dp-year" aria-label="Year"></select>
            <button type="button" class="dp-nav" data-nav="1" aria-label="Next month">&#8250;</button>
        </div>
        <div class="dp-dow" aria-hidden="true"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
        <div class="dp-grid" role="grid"></div>
        <div class="dp-foot">
            <button type="button" class="dp-clear">Clear</button>
            <button type="button" class="dp-close">Done</button>
        </div>
    </div>
</div>

<script>
(function () {
    var root = document.getElementById('dp-{{ $id }}');
    if (!root) { return; }
    var hidden = root.querySelector('input[type=hidden]');
    var trigger = root.querySelector('.dp-input');
    var textEl = root.querySelector('.dp-text');
    var pop = root.querySelector('.dp-pop');
    var monthSel = root.querySelector('.dp-month');
    var yearSel = root.querySelector('.dp-year');
    var grid = root.querySelector('.dp-grid');
    var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    function parse(s) {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || '');
        return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null;
    }
    function iso(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function same(a, b) { return a && b && iso(a) === iso(b); }

    var min = parse(root.dataset.min), max = parse(root.dataset.max);
    var selected = parse(hidden.value);
    var today = new Date();
    var view = selected ? new Date(selected.getFullYear(), selected.getMonth(), 1)
                        : new Date(Math.min(max.getFullYear() - 25, max.getFullYear()), 0, 1); // start near a typical adult birth year

    for (var i = 0; i < 12; i++) { monthSel.add(new Option(MONTHS[i], i)); }
    for (var y = max.getFullYear(); y >= min.getFullYear(); y--) { yearSel.add(new Option(y, y)); }

    function label() {
        textEl.textContent = selected
            ? MONTHS[selected.getMonth()] + ' ' + selected.getDate() + ', ' + selected.getFullYear()
            : 'Select your birthdate';
        trigger.classList.toggle('is-empty', !selected);
    }

    function render() {
        monthSel.value = view.getMonth();
        yearSel.value = view.getFullYear();
        grid.innerHTML = '';
        var first = new Date(view.getFullYear(), view.getMonth(), 1);
        var days = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
        for (var b = 0; b < first.getDay(); b++) { grid.appendChild(document.createElement('span')); }
        for (var d = 1; d <= days; d++) {
            var date = new Date(view.getFullYear(), view.getMonth(), d);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = d;
            btn.className = 'dp-day';
            btn.setAttribute('role', 'gridcell');
            if (same(date, today)) { btn.classList.add('is-today'); }
            if (same(date, selected)) { btn.classList.add('is-selected'); btn.setAttribute('aria-selected', 'true'); }
            if (date > max || date < min) { btn.disabled = true; }
            btn.addEventListener('click', (function (dt) { return function () { choose(dt); }; })(date));
            grid.appendChild(btn);
        }
    }

    function choose(date) {
        selected = date;
        hidden.value = iso(date);
        label(); render(); close();
        hidden.dispatchEvent(new Event('change', {bubbles: true}));
    }

    function open() {
        pop.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        root.classList.add('is-open');
        render();
    }
    function close() {
        pop.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        root.classList.remove('is-open');
    }

    function shift(n) {
        view = new Date(view.getFullYear(), view.getMonth() + n, 1);
        if (view > new Date(max.getFullYear(), max.getMonth(), 1)) { view = new Date(max.getFullYear(), max.getMonth(), 1); }
        if (view < new Date(min.getFullYear(), min.getMonth(), 1)) { view = new Date(min.getFullYear(), min.getMonth(), 1); }
        render();
    }

    trigger.addEventListener('click', function () { pop.hidden ? open() : close(); });
    root.querySelectorAll('.dp-nav').forEach(function (b) {
        b.addEventListener('click', function () { shift(+b.dataset.nav); });
    });
    monthSel.addEventListener('change', function () { view = new Date(view.getFullYear(), +monthSel.value, 1); render(); });
    yearSel.addEventListener('change', function () { view = new Date(+yearSel.value, view.getMonth(), 1); render(); });
    root.querySelector('.dp-clear').addEventListener('click', function () {
        selected = null; hidden.value = ''; label(); render();
    });
    root.querySelector('.dp-close').addEventListener('click', close);
    document.addEventListener('click', function (e) { if (!root.contains(e.target)) { close(); } });
    root.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); trigger.focus(); } });

    label();
})();
</script>

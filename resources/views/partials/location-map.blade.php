{{--
    Map + place search + live weather card (Leaflet / OpenStreetMap, no API key needed).
    Usage: @include('partials.location-map', ['editable' => true])                       (form: search, click or drag to pick a point)
           @include('partials.location-map', ['editable' => false, 'lat' => .., 'lon' => ..])  (read-only)

    Searching (Enter, the Search button, or choosing a suggestion) pins the place automatically.
    Optional: 'addressInput' => '#address'  (CSS selector of a text input that is filled with the found address)
--}}
@php
    $editable = $editable ?? false;
    $addressInput = $addressInput ?? '#address';
    $startLat = $editable ? old('latitude', $lat ?? null) : ($lat ?? null);
    $startLon = $editable ? old('longitude', $lon ?? null) : ($lon ?? null);
    $hasPoint = filled($startLat) && filled($startLon);
    $mapConfig = [
        'editable' => $editable,
        'hasPoint' => $hasPoint,
        'lat' => $hasPoint ? (float) $startLat : 14.5995,
        'lon' => $hasPoint ? (float) $startLon : 120.9842,
        'weatherUrl' => route('weather.show'),
        'searchUrl' => route('places.search'),
        'reverseUrl' => route('places.reverse'),
        'addressInput' => $addressInput,
    ];
@endphp

@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endonce

<div class="map-card">
    <div class="map-card-head">
        <span class="map-card-title">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
            {{ $editable ? 'Search your location or click the map' : 'Saved location' }}
        </span>
        @if ($editable)
            <button type="button" id="location-clear" class="btn btn-light btn-sm" hidden>Clear pin</button>
        @endif
    </div>

    @if ($editable)
        <div class="place-search" role="search">
            <div class="place-search-field" id="place-search-slot">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                <input type="text" id="place-search-input" placeholder="Search a school, landmark, address, barangay or city (e.g. FEU)…" autocomplete="off" aria-label="Search your location" aria-controls="place-suggestions" aria-autocomplete="list">
                <ul id="place-suggestions" class="place-suggestions" role="listbox" hidden></ul>
            </div>
            <button type="button" id="place-search-btn" class="btn btn-sm">Search</button>
            <button type="button" id="place-locate-btn" class="btn btn-light btn-sm" title="Use my current location">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/><circle cx="12" cy="12" r="7.5"/></svg>
                <span>My location</span>
            </button>
        </div>
        <p id="place-search-msg" class="place-search-msg" role="status" aria-live="polite" hidden></p>
    @endif

    <div id="location-map" class="location-map" role="application" aria-label="Map"></div>

    @if ($editable)
        <input type="hidden" id="latitude" name="latitude" value="{{ $hasPoint ? $startLat : '' }}">
        <input type="hidden" id="longitude" name="longitude" value="{{ $hasPoint ? $startLon : '' }}">
    @endif

    <div class="map-card-foot">
        <span id="location-coordinates" class="coord-chip">{{ $editable ? 'No pin selected' : '' }}</span>
        <span class="weather-chip" aria-live="polite">
            <span id="location-weather-icon" class="w-icon" aria-hidden="true">🌤️</span>
            <span id="location-weather">Weather has not been loaded.</span>
        </span>
    </div>
</div>

<script>
(function () {
    var cfg = @json($mapConfig);

    var weatherEl = document.getElementById('location-weather');
    var weatherIcon = document.getElementById('location-weather-icon');
    var coordsEl = document.getElementById('location-coordinates');
    var mapEl = document.getElementById('location-map');
    var clearBtn = document.getElementById('location-clear');
    var latInput = document.getElementById('latitude');
    var lonInput = document.getElementById('longitude');
    var searchInput = document.getElementById('place-search-input');
    var searchBtn = document.getElementById('place-search-btn');
    var locateBtn = document.getElementById('place-locate-btn');
    var listEl = document.getElementById('place-suggestions');
    var msgEl = document.getElementById('place-search-msg');
    var addressEl = cfg.addressInput ? document.querySelector(cfg.addressInput) : null;
    var map = null, marker = null, lastRequest = 0, searchSeq = 0, debounce = null;
    var suggestions = [], activeIndex = -1;
    var autoAddress = false; // true while the address box holds text we filled in ourselves

    function iconFor(text) {
        text = (text || '').toLowerCase();
        if (text.indexOf('thunder') > -1) { return '\u26C8\uFE0F'; }
        if (text.indexOf('rain') > -1 || text.indexOf('drizzle') > -1) { return '\uD83C\uDF27\uFE0F'; }
        if (text.indexOf('snow') > -1) { return '\u2744\uFE0F'; }
        if (text.indexOf('mist') > -1 || text.indexOf('fog') > -1 || text.indexOf('haze') > -1 || text.indexOf('smoke') > -1) { return '\uD83C\uDF2B\uFE0F'; }
        if (text.indexOf('few clouds') > -1 || text.indexOf('scattered') > -1) { return '\u26C5'; }
        if (text.indexOf('cloud') > -1 || text.indexOf('overcast') > -1) { return '\u2601\uFE0F'; }
        if (text.indexOf('clear') > -1) { return '\u2600\uFE0F'; }
        return '\uD83C\uDF24\uFE0F';
    }

    function say(text, isError) {
        if (!msgEl) { return; }
        msgEl.textContent = text || '';
        msgEl.hidden = !text;
        msgEl.classList.toggle('is-error', !!isError);
    }

    function getJson(url) {
        return fetch(url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'})
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); });
    }

    function loadWeather(lat, lon) {
        var id = ++lastRequest;
        weatherEl.textContent = 'Loading weather...';
        getJson(cfg.weatherUrl + '?lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lon))
            .then(function (w) {
                if (id !== lastRequest) { return; } // a newer pin superseded this request
                weatherIcon.textContent = iconFor(w.description);
                weatherEl.textContent = w.place + ' \u00B7 ' + Math.round(w.temperature) + '\u00B0C \u00B7 '
                    + w.description + ' \u00B7 humidity ' + w.humidity + '%';
            })
            .catch(function () {
                if (id === lastRequest) { weatherEl.textContent = 'Weather is currently unavailable.'; }
            });
    }

    function showCoords(lat, lon) {
        coordsEl.textContent = lat.toFixed(5) + ', ' + lon.toFixed(5);
    }

    function fillAddress(text) {
        if (!addressEl || !text) { return; }
        // Never overwrite something the person typed themselves.
        if (addressEl.value.trim() === '' || autoAddress) {
            addressEl.value = text.slice(0, 500);
            autoAddress = true;
        }
    }

    if (addressEl) {
        addressEl.addEventListener('input', function () { autoAddress = false; });
    }

    function reverseGeocode(lat, lon) {
        if (!addressEl) { return; }
        getJson(cfg.reverseUrl + '?lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lon))
            .then(function (res) { if (res && res.address) { fillAddress(res.address); } })
            .catch(function () { /* the address box is optional */ });
    }

    function setLocation(lat, lon, opts) {
        opts = opts || {};
        if (cfg.editable) {
            latInput.value = lat.toFixed(7);
            lonInput.value = lon.toFixed(7);
            clearBtn.hidden = false;
        }
        showCoords(lat, lon);
        if (map) {
            if (marker) { marker.setLatLng([lat, lon]); }
            else { marker = makeMarker(lat, lon); }
        }
        if (opts.address) { fillAddress(opts.address); } else if (opts.reverse !== false) { reverseGeocode(lat, lon); }
        loadWeather(lat, lon);
    }

    // Fly to a point (from a search result or GPS) and drop the pin there.
    function goTo(lat, lon, opts) {
        opts = opts || {};
        if (map) {
            if (opts.bounds) { map.fitBounds(opts.bounds, {maxZoom: 18, padding: [30, 30]}); }
            else { map.setView([lat, lon], 17); }
        }
        setLocation(lat, lon, opts);
        say('');
    }

    function clearLocation() {
        latInput.value = '';
        lonInput.value = '';
        coordsEl.textContent = 'No pin selected';
        clearBtn.hidden = true;
        if (marker) { map.removeLayer(marker); marker = null; }
        if (searchInput) { searchInput.value = ''; }
        hideSuggestions();
        say('');
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', clearLocation);
        clearBtn.hidden = !cfg.hasPoint;
    }

    // ---- Search: suggestions as you type, and Enter / Search pins the best match ----
    function hideSuggestions() {
        suggestions = [];
        activeIndex = -1;
        if (listEl) { listEl.hidden = true; listEl.innerHTML = ''; }
    }

    function pickResult(r) {
        hideSuggestions();
        if (searchInput) { searchInput.value = r.name; }
        goTo(r.lat, r.lon, {address: r.address, bounds: r.bounds});
    }

    function renderSuggestions(results) {
        suggestions = results;
        activeIndex = -1;
        listEl.innerHTML = '';
        results.forEach(function (r, i) {
            var li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.dataset.index = i;
            var strong = document.createElement('strong');
            strong.textContent = r.name;
            var span = document.createElement('span');
            span.textContent = r.address;
            li.appendChild(strong);
            li.appendChild(span);
            // mousedown fires before the input loses focus, so the pick is never lost
            li.addEventListener('mousedown', function (e) { e.preventDefault(); pickResult(r); });
            listEl.appendChild(li);
        });
        listEl.hidden = results.length === 0;
    }

    function highlight(i) {
        var items = listEl.children;
        if (!items.length) { return; }
        activeIndex = (i + items.length) % items.length;
        for (var n = 0; n < items.length; n++) { items[n].classList.toggle('is-active', n === activeIndex); }
    }

    function fetchPlaces(q) {
        return getJson(cfg.searchUrl + '?q=' + encodeURIComponent(q)).then(function (res) { return res.results || []; });
    }

    function typeahead() {
        var q = searchInput.value.trim();
        if (q.length < 3) { hideSuggestions(); return; }
        var seq = ++searchSeq;
        fetchPlaces(q).then(function (results) {
            if (seq === searchSeq && document.activeElement === searchInput) { renderSuggestions(results); }
        }).catch(function () { /* silent while typing; Enter reports errors */ });
    }

    // Enter / Search button: pin the highlighted suggestion, otherwise the best match.
    function submitSearch() {
        var q = searchInput ? searchInput.value.trim() : '';
        if (!q) { say('Type a place or address to search.', true); return; }
        if (activeIndex > -1 && suggestions[activeIndex]) { pickResult(suggestions[activeIndex]); return; }

        var seq = ++searchSeq;
        clearTimeout(debounce);
        say('Searching\u2026');
        fetchPlaces(q).then(function (results) {
            if (seq !== searchSeq) { return; }
            if (!results.length) {
                hideSuggestions();
                say('No places found for \u201C' + q + '\u201D. Try a nearby landmark or the city name.', true);
                return;
            }
            pickResult(results[0]);
        }).catch(function () {
            say('Search is unavailable right now. You can still click the map to drop a pin.', true);
        });
    }

    if (searchBtn) { searchBtn.addEventListener('click', submitSearch); }
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounce);
            debounce = setTimeout(typeahead, 350);
        });
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); submitSearch(); }
            else if (e.key === 'ArrowDown') { e.preventDefault(); highlight(activeIndex + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(activeIndex - 1); }
            else if (e.key === 'Escape') { hideSuggestions(); }
        });
        searchInput.addEventListener('blur', function () { setTimeout(hideSuggestions, 150); });
    }

    // Pressing Enter while searching must never submit the whole application form.
    var parentForm = mapEl.closest('form');
    if (parentForm) {
        parentForm.addEventListener('submit', function (e) {
            if (searchInput && document.activeElement === searchInput) { e.preventDefault(); }
        });
    }

    // ---- "My location" (browser GPS) ----
    if (locateBtn) {
        locateBtn.addEventListener('click', function () {
            if (!navigator.geolocation) { say('Your browser can\u2019t share its location. Search for your address instead.', true); return; }
            say('Finding your location\u2026');
            navigator.geolocation.getCurrentPosition(function (pos) {
                goTo(pos.coords.latitude, pos.coords.longitude);
            }, function () {
                say('We couldn\u2019t get your location. Allow location access, or search for your address.', true);
            }, {enableHighAccuracy: true, timeout: 10000});
        });
    }

    // ---- Map ----
    function makeMarker(lat, lon) {
        var pin = L.divIcon({
            className: 'map-pin',
            html: '<svg viewBox="0 0 24 32" width="32" height="42" aria-hidden="true"><path d="M12 31s-10-9.4-10-18a10 10 0 0 1 20 0c0 8.600-10 18-10 18z" fill="#810000" stroke="#fff" stroke-width="1.6"/><circle cx="12" cy="13" r="4" fill="#fff"/></svg>',
            iconSize: [32, 42],
            iconAnchor: [16, 42]
        });
        var m = L.marker([lat, lon], {icon: pin, draggable: !!cfg.editable, title: cfg.editable ? 'Drag to fine-tune your location' : 'Saved location'}).addTo(map);
        if (cfg.editable) {
            m.on('dragend', function () {
                var p = m.getLatLng();
                setLocation(p.lat, p.lng);
            });
        }
        return m;
    }

    function initMap() {
        if (typeof L === 'undefined') {
            mapEl.innerHTML = '<div class="map-notice"><strong>Map could not load</strong>'
                + '<span>Check your internet connection and reload the page.</span></div>';
            return;
        }
        map = L.map(mapEl, {center: [cfg.lat, cfg.lon], zoom: cfg.hasPoint ? 16 : 13, scrollWheelZoom: true});
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        if (cfg.hasPoint) { marker = makeMarker(cfg.lat, cfg.lon); }
        if (cfg.editable) {
            map.on('click', function (event) { setLocation(event.latlng.lat, event.latlng.lng); });
        }
        // The card can be laid out after the script runs; make sure tiles fill it.
        setTimeout(function () { map.invalidateSize(); }, 200);
    }

    // Initial state
    if (cfg.hasPoint) { showCoords(cfg.lat, cfg.lon); }
    loadWeather(cfg.lat, cfg.lon);
    initMap();
})();
</script>

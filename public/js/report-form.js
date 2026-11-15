// Form laporan: pilih jenis, pratinjau foto, dan pin lokasi di peta kampus
$(document).ready(function () {
    const cfg = window.FINDIT_PICKER;

    // ---------- Jenis laporan: tampilkan ciri rahasia khusus barang temuan ----------
    function applyType() {
        const type = $('input[name="type"]:checked').val() || 'lost';
        const found = type === 'found';
        $('.secret-section').toggle(found);
        $('#photo-hint').text(found ? '(wajib untuk barang temuan)' : '(opsional, tapi sangat membantu)');
        const label = $('label[for="event_at"]');
        label.text(label.data(found ? 'label-found' : 'label-lost'));
    }
    $('input[name="type"]').on('change', applyType);
    applyType();

    // ---------- Hitung karakter deskripsi ----------
    $('#description').on('input', function () {
        const n = $(this).val().length;
        $('#desc-count').text(n).css('color', n >= 20 ? 'var(--found)' : '');
    }).trigger('input');

    // ---------- Pratinjau foto sebelum diunggah ----------
    $('#photo').on('change', function () {
        const file = this.files[0];
        if (!file) return;
        if (file.size > 4 * 1024 * 1024) {
            alert('Ukuran foto maksimal 4 MB.');
            $(this).val('');
            return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
            $('#photo-preview').attr('src', e.target.result).show();
            $('#photo-label').text(file.name);
        };
        reader.readAsDataURL(file);
    });

    // ---------- Peta pemilih lokasi ----------
    const startLat = parseFloat($('#lat').val());
    const startLng = parseFloat($('#lng').val());
    const hasPoint = !isNaN(startLat) && !isNaN(startLng);
    const start = hasPoint ? [startLat, startLng] : cfg.center;

    const map = L.map('pick-map', { scrollWheelZoom: false }).setView(start, hasPoint ? 18 : 17);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    // Batas area kampus (lingkaran tipis sebagai panduan)
    L.circle(cfg.center, { radius: cfg.radius, color: '#C1272D', weight: 1, dashArray: '4 6', fill: false, interactive: false }).addTo(map);

    const pin = L.marker(start, {
        draggable: true,
        icon: L.divIcon({ className: 'pin-marker', html: '<i class="bi bi-geo-alt-fill"></i>', iconSize: [38, 38], iconAnchor: [19, 36] }),
    });

    function setPoint(latlng) {
        pin.setLatLng(latlng);
        if (!map.hasLayer(pin)) pin.addTo(map);
        $('#lat').val(latlng.lat.toFixed(7));
        $('#lng').val(latlng.lng.toFixed(7));
        $('#coord-text').text(latlng.lat.toFixed(5) + ', ' + latlng.lng.toFixed(5));
    }

    if (hasPoint) {
        setPoint(L.latLng(startLat, startLng));
    }

    map.on('click', function (e) { setPoint(e.latlng); });
    pin.on('dragend', function () { setPoint(pin.getLatLng()); });

    // Pilih gedung: peta terbang ke gedung dan pin dipindahkan ke sana
    $('#campus_location_id').on('change', function () {
        const opt = $(this).find(':selected');
        if (!opt.data('lat')) return;
        const latlng = L.latLng(opt.data('lat'), opt.data('lng'));
        map.flyTo(latlng, 18, { duration: 0.6 });
        setPoint(latlng);
    });

    // Cegah kirim tanpa titik lokasi
    $('#report-form').on('submit', function (e) {
        if (!$('#lat').val()) {
            e.preventDefault();
            alert('Tentukan titik lokasi di peta dulu.');
            $('html, body').animate({ scrollTop: $('#pick-map').offset().top - 120 }, 300);
        }
    });
});

// Halaman detail: peta mini, kode QR, dan salin tautan
$(document).ready(function () {
    const r = window.FINDIT_REPORT;

    const map = L.map('mini-map', {
        zoomControl: false, dragging: !L.Browser.mobile, scrollWheelZoom: false,
    }).setView([r.lat, r.lng], 18);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    const inner = r.thumb
        ? '<img src="' + r.thumb + '" alt="">'
        : '<div class="pm-empty"><i class="bi bi-' + r.icon + '"></i></div>';
    const badge = r.type === 'lost' ? 'search' : 'check-lg';
    L.marker([r.lat, r.lng], {
        icon: L.divIcon({
            className: 'pm-wrap',
            html: '<div class="pm ' + r.type + '">' + inner + '<span class="pm-badge"><i class="bi bi-' + badge + '"></i></span></div>',
            iconSize: [54, 54],
            iconAnchor: [27, 27],
        }),
    }).addTo(map);

    // Kode QR dibuat saat modal bagikan dibuka
    let qrMade = false;
    $('#share-modal').on('shown.bs.modal', function () {
        if (qrMade) return;
        new QRCode(document.getElementById('share-qr'), { text: r.shareUrl, width: 180, height: 180, correctLevel: QRCode.CorrectLevel.M });
        qrMade = true;
    });

    $('#copy-link').on('click', function () {
        const btn = $(this);
        const done = () => btn.html('<i class="bi bi-check2 me-1"></i>Tersalin');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(r.shareUrl).then(done);
        } else {
            $('#share-link').select();
            document.execCommand('copy');
            done();
        }
    });
});

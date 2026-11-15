// Peta Global: marker foto ala peta Instagram di atas OpenStreetMap kampus
$(document).ready(function () {
    const cfg = window.FINDIT_MAP;
    const isMobile = () => window.innerWidth < 992;

    const map = L.map('global-map', { zoomControl: false }).setView(cfg.center, cfg.zoom);
    L.control.zoom({ position: 'topright' }).addTo(map);

    // Tile OpenStreetMap standar (sama seperti peta whereis Telkom University)
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    // Kelompok marker: foto laporan terbaru tampil di depan + jumlah laporan
    const cluster = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 56,
        spiderfyOnMaxZoom: true,
        iconCreateFunction: function (c) {
            const reports = c.getAllChildMarkers().map((m) => m.options.report);
            reports.sort((a, b) => new Date(b.event_at) - new Date(a.event_at));
            const top = reports[0];
            const inner = top.thumb
                ? '<img src="' + top.thumb + '" alt="">'
                : '<div class="pm-empty"><i class="bi bi-' + top.icon + '"></i></div>';
            return L.divIcon({
                className: 'pc-wrap',
                html: '<div class="pc">' + inner + '<span class="pc-count">' + reports.length + '</span></div>',
                iconSize: [62, 62],
                iconAnchor: [31, 31],
            });
        },
    }).addTo(map);

    // Label gedung diletakkan di lapisan khusus di bawah marker foto
    map.createPane('buildingPane');
    map.getPane('buildingPane').style.zIndex = 450;
    map.getPane('buildingPane').style.pointerEvents = 'none';
    const buildings = L.layerGroup().addTo(map);
    const markersById = {};
    let activeMarker = null;

    // ---------- Data gedung kampus ----------
    $.ajax({
        type: 'GET',
        url: cfg.locationsUrl,
        dataType: 'json',
        success: function (rsp) {
            $.each(rsp.data, function (i, b) {
                L.marker([b.lat, b.lng], {
                    icon: L.divIcon({ className: '', html: '<div class="bld-dot"></div>', iconSize: [8, 8] }),
                    interactive: false,
                    keyboard: false,
                    pane: 'buildingPane',
                })
                    .bindTooltip(b.short_name, { permanent: true, direction: 'right', className: 'bld-label', offset: [4, 0], pane: 'buildingPane' })
                    .addTo(buildings);
            });
            $('.bld-label').toggle(map.getZoom() >= 17);
        },
    });

    // ---------- Data laporan (AJAX JSON → dirender di browser) ----------
    function photoMarker(r) {
        const inner = r.thumb
            ? '<img src="' + r.thumb + '" alt="' + escapeHtml(r.title) + '">'
            : '<div class="pm-empty"><i class="bi bi-' + r.icon + '"></i></div>';
        const badge = r.type === 'lost' ? 'search' : 'check-lg';
        return L.divIcon({
            className: 'pm-wrap',
            html: '<div class="pm ' + r.type + '">' + inner + '<span class="pm-badge"><i class="bi bi-' + badge + '"></i></span></div>',
            iconSize: [54, 54],
            iconAnchor: [27, 27],
            popupAnchor: [0, -30],
        });
    }

    function typeLabel(r) {
        return r.type === 'lost'
            ? '<span class="type-label lost"><i class="bi bi-search"></i> Hilang</span>'
            : '<span class="type-label found"><i class="bi bi-check-lg"></i> Ditemukan</span>';
    }

    function previewHtml(r) {
        const photo = r.thumb
            ? '<img src="' + r.thumb + '" alt="">'
            : '<div class="photo-empty ' + r.type + '"><i class="bi bi-' + r.icon + '"></i></div>';
        const status = r.status === 'in_claim' ? ' <span class="status-badge s-in_claim">Proses klaim</span>' : '';
        return '<div class="preview-card">' +
            '<div class="pv-photo">' + photo + '</div>' +
            '<div class="pv-body">' + typeLabel(r) + status +
                '<div class="pv-title">' + escapeHtml(r.title) + '</div>' +
                '<div class="pv-meta">' + r.time_ago + ' · ' + escapeHtml(r.place_full) + '</div>' +
                '<a href="' + r.url + '" class="btn btn-ink btn-sm w-100">Lihat detail</a>' +
            '</div></div>';
    }

    function showSheet(r) {
        const photo = r.thumb
            ? '<img src="' + r.thumb + '" class="thumb thumb-lg" alt="">'
            : '<div class="thumb thumb-lg"><div class="photo-empty ' + r.type + '"><i class="bi bi-' + r.icon + '"></i></div></div>';
        $('#sheet-content').html(
            photo +
            '<div class="flex-grow-1 min-w-0">' + typeLabel(r) +
                '<div class="fw-bold mt-1 text-truncate">' + escapeHtml(r.title) + '</div>' +
                '<div class="small-caption text-truncate">' + r.time_ago + ' · ' + escapeHtml(r.place_full) + '</div>' +
                '<a href="' + r.url + '" class="btn btn-ink btn-sm mt-2">Lihat detail</a>' +
            '</div>'
        );
        $('#map-sheet').addClass('show');
    }

    function setActive(marker) {
        if (activeMarker && activeMarker._icon) {
            $(activeMarker._icon).removeClass('is-active');
        }
        activeMarker = marker;
        if (marker._icon) {
            $(marker._icon).addClass('is-active');
        }
        $('#report-list .list-row').removeClass('is-active');
        $('#report-list .list-row[data-id="' + marker.options.report.id + '"]').addClass('is-active');
    }

    function openReport(marker) {
        const r = marker.options.report;
        setActive(marker);
        if (isMobile()) {
            map.closePopup();
            showSheet(r);
        } else {
            marker.bindPopup(previewHtml(r), { maxWidth: 260, minWidth: 260 }).openPopup();
        }
    }

    function renderList(reports) {
        const list = $('#report-list').empty();
        $('#list-count').text(reports.length + ' laporan');
        if (reports.length === 0) {
            list.append('<div class="empty-state"><i class="bi bi-binoculars"></i>Belum ada laporan untuk filter ini.</div>');
            return;
        }
        $.each(reports, function (i, r) {
            const photo = r.thumb
                ? '<img src="' + r.thumb + '" class="thumb" alt="">'
                : '<div class="thumb"><div class="photo-empty ' + r.type + '"><i class="bi bi-' + r.icon + '"></i></div></div>';
            list.append(
                '<div class="list-row" data-id="' + r.id + '" tabindex="0">' + photo +
                    '<div class="min-w-0">' + typeLabel(r) +
                        '<div class="rc-title fw-bold mt-1 text-truncate">' + escapeHtml(r.title) + '</div>' +
                        '<div class="small-caption text-truncate">' + r.time_ago + ' · ' + escapeHtml(r.place) + '</div>' +
                    '</div>' +
                '</div>'
            );
        });
    }

    function filters() {
        const data = $('#map-filter').serializeArray().reduce((o, f) => { o[f.name] = f.value; return o; }, {});
        if (isMobile()) {
            data.type = $('input[name="m-type"]:checked').val();
        }
        return data;
    }

    let firstLoad = true;
    function loadReports() {
        $('#map-loading').show();
        $.ajax({
            type: 'GET',
            url: cfg.reportsUrl,
            data: filters(),
            dataType: 'json',
            success: function (rsp) {
                cluster.clearLayers();
                Object.keys(markersById).forEach((k) => delete markersById[k]);

                $.each(rsp.data, function (i, r) {
                    const marker = L.marker([r.lat, r.lng], { icon: photoMarker(r), report: r, title: r.title });
                    marker.on('click', function () { openReport(marker); });
                    markersById[r.id] = marker;
                    cluster.addLayer(marker);
                });

                renderList(rsp.data);
                $('#map-count').text(rsp.count + ' laporan aktif');

                if (firstLoad && rsp.data.length) {
                    map.fitBounds(cluster.getBounds(), { padding: [60, 60], maxZoom: 17 });
                }
                firstLoad = false;
            },
            error: function () {
                $('#map-count').text('Gagal memuat laporan');
            },
            complete: function () {
                $('#map-loading').hide();
            },
        });
    }

    // Klik item di daftar → terbang ke marker dan buka pratinjau
    $(document).on('click keypress', '#report-list .list-row', function (e) {
        if (e.type === 'keypress' && e.key !== 'Enter') return;
        const marker = markersById[$(this).data('id')];
        if (!marker) return;
        cluster.zoomToShowLayer(marker, function () {
            map.panTo(marker.getLatLng());
            openReport(marker);
        });
    });

    // Filter berubah → muat ulang marker tanpa reload halaman
    let typingTimer;
    $('#map-filter').on('change', 'select, input[type=radio]', loadReports);
    $('#map-filter').on('input', 'input[type=search]', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(loadReports, 400);
    });
    $('#map-filter').on('submit', function (e) { e.preventDefault(); loadReports(); });
    $('input[name="m-type"]').on('change', loadReports);

    // Tampilkan / sembunyikan label gedung
    function toggleBuildings(on) {
        if (on) { buildings.addTo(map); } else { map.removeLayer(buildings); }
        $('#toggle-buildings').prop('checked', on);
        $('#btn-buildings').toggleClass('is-on', on);
    }
    $('#toggle-buildings').on('change', function () { toggleBuildings(this.checked); });
    $('#btn-buildings').on('click', function () { toggleBuildings(!$(this).hasClass('is-on')); });

    // Lokasi saya (Geolocation API browser)
    let me = null;
    $('#btn-locate').on('click', function () {
        if (!navigator.geolocation) {
            alert('Browser tidak mendukung lokasi.');
            return;
        }
        navigator.geolocation.getCurrentPosition(function (pos) {
            const latlng = [pos.coords.latitude, pos.coords.longitude];
            if (me) map.removeLayer(me);
            me = L.circleMarker(latlng, { radius: 8, color: '#fff', weight: 3, fillColor: '#1D4F8A', fillOpacity: 1 }).addTo(map);
            map.flyTo(latlng, 18);
        }, function () {
            alert('Izin lokasi ditolak atau lokasi tidak tersedia.');
        });
    });

    $('.sheet-close').on('click', function () { $('#map-sheet').removeClass('show'); });
    map.on('click', function () { $('#map-sheet').removeClass('show'); });

    // Kecilkan label gedung saat peta di-zoom jauh
    map.on('zoomend', function () {
        $('.bld-label').toggle(map.getZoom() >= 17);
    });

    loadReports();

    // Sembunyikan pesan flash setelah 4 detik
    setTimeout(function () { $('#map-toast').css('opacity', 0); setTimeout(() => $('#map-toast').remove(), 400); }, 4000);
});

// Tabel admin memakai jQuery DataTables dengan sumber data JSON (AJAX)
$(document).ready(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');

    const bahasa = {
        search: 'Cari:',
        lengthMenu: 'Tampilkan _MENU_ baris',
        info: '_START_–_END_ dari _TOTAL_ data',
        infoEmpty: 'Tidak ada data',
        infoFiltered: '(disaring dari _MAX_ data)',
        zeroRecords: 'Tidak ada data yang cocok',
        emptyTable: 'Belum ada data',
        loadingRecords: 'Memuat…',
        paginate: { first: '«', last: '»', next: '›', previous: '‹' },
    };

    function postButton(url, label, cls, confirmText) {
        return '<form method="POST" action="' + url + '" style="display:inline" onsubmit="return confirm(\'' + confirmText + '\')">' +
            '<input type="hidden" name="_token" value="' + csrf + '">' +
            '<button class="btn btn-sm ' + cls + '">' + label + '</button></form>';
    }

    function thumbCell(thumb, title) {
        const img = thumb
            ? '<img src="' + thumb + '" class="thumb" style="width:40px;height:40px;border-radius:8px" alt="">'
            : '<div class="thumb" style="width:40px;height:40px;border-radius:8px"><div class="photo-empty found" style="font-size:16px"><i class="bi bi-box-seam"></i></div></div>';
        return '<div class="d-flex align-items-center gap-2">' + img + '<span class="fw-semibold">' + escapeHtml(title) + '</span></div>';
    }

    const typeHtml = (r) => r.type === 'lost'
        ? '<span class="type-label lost"><i class="bi bi-search"></i> Hilang</span>'
        : '<span class="type-label found"><i class="bi bi-check-lg"></i> Ditemukan</span>';

    // ---------- Laporan ----------
    if ($('#reports-table').length) {
        const table = new DataTable('#reports-table', {
            ajax: { url: $('#reports-table').data('url'), dataSrc: 'data' },
            order: [[6, 'desc']],
            pageLength: 10,
            language: bahasa,
            columns: [
                { data: 'title', render: (d, t, r) => t === 'display' ? thumbCell(r.thumb, r.title) : d },
                { data: 'type_label', render: (d, t, r) => t === 'display' ? typeHtml(r) : d },
                { data: 'category' },
                { data: 'place' },
                { data: 'reporter' },
                {
                    data: 'status_label',
                    render: (d, t, r) => t === 'display'
                        ? '<span class="status-badge s-' + r.status + '">' + d + '</span>' + (r.is_hidden ? ' <span class="status-badge s-rejected">Tersembunyi</span>' : '')
                        : d,
                },
                { data: 'created_at' },
                {
                    data: null, orderable: false, searchable: false, className: 'text-end text-nowrap',
                    render: (d, t, r) => '<a href="' + r.url + '" class="btn btn-sm btn-outline-ink">Lihat</a> ' +
                        postButton(r.toggle_url, r.is_hidden ? 'Tampilkan' : 'Sembunyikan', 'btn-outline-ink', r.is_hidden ? 'Tampilkan lagi laporan ini?' : 'Sembunyikan laporan ini dari peta?') +
                        (['open', 'in_claim'].includes(r.status) ? ' ' + postButton(r.close_url, 'Tutup', 'btn-outline-danger', 'Tutup laporan ini?') : ''),
                },
            ],
        });
        $('#filter-type').on('change', function () { table.column(1).search(this.value).draw(); });
        $('#filter-status').on('change', function () { table.column(5).search(this.value).draw(); });
    }

    // ---------- Klaim ----------
    if ($('#claims-table').length) {
        const table = new DataTable('#claims-table', {
            ajax: { url: $('#claims-table').data('url'), dataSrc: 'data' },
            order: [[4, 'asc']],
            pageLength: 10,
            language: bahasa,
            columns: [
                { data: 'item', render: (d, t, r) => t === 'display' ? thumbCell(r.thumb, r.item) : d },
                { data: 'claimant' },
                { data: 'nim' },
                { data: 'status_label', render: (d, t, r) => t === 'display' ? '<span class="status-badge s-' + r.status + '">' + d + '</span>' : d },
                { data: 'created_at' },
                {
                    data: null, orderable: false, searchable: false, className: 'text-end',
                    render: (d, t, r) => '<a href="' + r.url + '" class="btn btn-sm ' + (r.status === 'pending' ? 'btn-ink' : 'btn-outline-ink') + '">' + (r.status === 'pending' ? 'Tinjau' : 'Lihat') + '</a>',
                },
            ],
        });
        const filter = $('#filter-claim-status');
        table.column(3).search(filter.val());
        filter.on('change', function () { table.column(3).search(this.value).draw(); });
    }

    // ---------- Pengguna ----------
    if ($('#users-table').length) {
        new DataTable('#users-table', {
            ajax: { url: $('#users-table').data('url'), dataSrc: 'data' },
            order: [[0, 'asc']],
            pageLength: 10,
            language: bahasa,
            columns: [
                { data: 'name', render: (d) => '<span class="fw-semibold">' + escapeHtml(d) + '</span>' },
                { data: 'nim' },
                { data: 'email' },
                { data: 'role', render: (d) => d === 'admin' ? '<span class="status-badge s-open">Admin</span>' : 'Pengguna' },
                { data: 'reports' },
                { data: 'claims' },
                {
                    data: 'is_active',
                    render: (d, t) => t === 'display'
                        ? (d ? '<span class="status-badge s-resolved">Aktif</span>' : '<span class="status-badge s-rejected">Nonaktif</span>')
                        : (d ? 'Aktif' : 'Nonaktif'),
                },
                {
                    data: null, orderable: false, searchable: false, className: 'text-end',
                    render: (d, t, r) => r.is_me ? '<span class="small-caption">Akun kamu</span>'
                        : postButton(r.toggle_url, r.is_active ? 'Nonaktifkan' : 'Aktifkan', r.is_active ? 'btn-outline-danger' : 'btn-outline-ink',
                            r.is_active ? 'Nonaktifkan akun ini?' : 'Aktifkan kembali akun ini?'),
                },
            ],
        });
    }
});

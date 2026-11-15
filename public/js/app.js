// Sumber tile peta dipakai bersama oleh Peta Global, form laporan, dan peta mini.
// Cukup ubah di sini bila ingin memakai penyedia tile lain.
const FINDIT_TILES = {
    url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
    options: {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    },
};

// Skrip umum: token CSRF untuk AJAX, notifikasi lonceng, tombol lihat kata sandi
$(document).ready(function () {
    // Semua request AJAX jQuery membawa token CSRF Laravel
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    });

    // Tampilkan / sembunyikan kata sandi
    $(document).on('click', '[data-toggle-password]', function () {
        const input = $($(this).data('toggle-password'));
        const show = input.attr('type') === 'password';
        input.attr('type', show ? 'text' : 'password');
        $(this).find('i').toggleClass('bi-eye', !show).toggleClass('bi-eye-slash', show);
    });

    // Konfirmasi sebelum aksi penting (hapus, tutup, dll.)
    $(document).on('submit', 'form[data-confirm]', function (e) {
        if (!confirm($(this).data('confirm'))) {
            e.preventDefault();
        }
    });

    // ---------- Notifikasi lonceng (AJAX JSON) ----------
    const bell = $('#bell-btn');
    if (bell.length) {
        loadAlerts();
        setInterval(loadAlerts, 30000);
    }

    function loadAlerts() {
        $.ajax({
            type: 'GET',
            url: '/ajax/alerts',
            dataType: 'json',
            success: function (rsp) {
                const count = $('#bell-count');
                count.text(rsp.unread).toggle(rsp.unread > 0);

                const list = $('#bell-list').empty();
                if (rsp.data.length === 0) {
                    list.append('<div class="empty-state py-4"><i class="bi bi-bell-slash"></i>Belum ada notifikasi</div>');
                    return;
                }
                $.each(rsp.data, function (i, a) {
                    list.append(
                        '<a class="alert-item ' + (a.read ? '' : 'unread') + '" href="' + a.url + '">' +
                            '<i class="bi bi-' + a.icon + '"></i>' +
                            '<div><div class="alert-item-title">' + escapeHtml(a.title) + '</div>' +
                            (a.body ? '<div class="alert-item-body">' + escapeHtml(a.body) + '</div>' : '') +
                            '<div class="alert-item-time">' + a.time + '</div></div>' +
                        '</a>'
                    );
                });
            },
        });
    }
});

// Mencegah teks dari pengguna dibaca sebagai HTML. Tanda kutip ikut di-escape
// karena teks ini juga dipakai di dalam atribut, misalnya alt="..."
function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    return String(text == null ? '' : text).replace(/[&<>"']/g, (c) => map[c]);
}

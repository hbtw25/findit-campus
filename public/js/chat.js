// Chat serah terima: memuat pesan baru tiap 5 detik (AJAX polling) dan mengirim pesan tanpa reload
$(document).ready(function () {
    const cfg = window.FINDIT_CHAT;
    const box = $('#chat-box');
    let lastId = 0;

    function addBubble(m) {
        $('#chat-empty').remove();
        box.append(
            '<div class="bubble ' + (m.mine ? 'mine' : '') + '">' + escapeHtml(m.body) +
            '<span class="meta">' + (m.mine ? 'Kamu' : escapeHtml(m.name)) + ' · ' + m.time + '</span></div>'
        );
        lastId = Math.max(lastId, m.id);
    }

    function scrollBottom() {
        box.scrollTop(box[0].scrollHeight);
    }

    function load() {
        $.ajax({
            type: 'GET',
            url: cfg.url,
            data: { after: lastId },
            dataType: 'json',
            success: function (rsp) {
                if (rsp.data.length) {
                    $.each(rsp.data, function (i, m) { addBubble(m); });
                    scrollBottom();
                }
            },
        });
    }

    $('#chat-form').on('submit', function (e) {
        e.preventDefault();
        const input = $('#chat-input');
        const body = $.trim(input.val());
        if (!body) return;

        input.prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: cfg.url,
            data: { body: body },
            dataType: 'json',
            success: function (rsp) {
                input.val('');
                addBubble(rsp.data);
                scrollBottom();
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Pesan gagal dikirim.');
            },
            complete: function () {
                input.prop('disabled', false).focus();
            },
        });
    });

    load();
    if (cfg.open) {
        setInterval(load, 5000);
    }
});

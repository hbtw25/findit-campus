<div class="modal fade" id="share-modal" tabindex="-1" aria-labelledby="share-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 18px">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title h5" id="share-title">Bagikan laporan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Sebarkan ke grup angkatan, story Instagram, atau papan pengumuman gedung.</p>
                <div class="d-flex justify-content-center my-3"><div id="share-qr" class="p-2 bg-white border rounded-3"></div></div>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="share-link" value="{{ $report->share_url }}" readonly aria-label="Tautan laporan">
                    <button class="btn btn-ink" type="button" id="copy-link"><i class="bi bi-clipboard me-1"></i>Salin</button>
                </div>
                <div class="d-grid gap-2">
                    <a href="{{ route('reports.poster', $report) }}" target="_blank" class="btn btn-outline-ink"><i class="bi bi-printer me-1"></i>Poster siap cetak & gambar story</a>
                    <a href="https://wa.me/?text={{ urlencode(($report->isLost() ? 'Dicari: ' : 'Ditemukan: ') . $report->title . ' — ' . $report->share_url) }}" target="_blank" rel="noopener" class="btn btn-outline-ink"><i class="bi bi-whatsapp me-1"></i>Kirim ke WhatsApp</a>
                </div>
            </div>
        </div>
    </div>
</div>

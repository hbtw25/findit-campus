{{-- Label jenis laporan: warna + ikon + teks, supaya tidak hanya dibedakan lewat warna --}}
@if ($type === 'lost')
    <span class="type-label lost"><i class="bi bi-search"></i> Hilang</span>
@else
    <span class="type-label found"><i class="bi bi-check-lg"></i> Ditemukan</span>
@endif

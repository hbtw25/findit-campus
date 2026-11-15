<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poster · {{ $report->title }} · FindIt Campus</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body { background: #ECE8E2; }
        .toolbar { max-width: 148mm; margin: 1.5rem auto 1rem; display: flex; gap: .5rem; flex-wrap: wrap; }
        /* Poster A5 */
        .poster { width: 148mm; min-height: 210mm; margin: 0 auto 2rem; background: #fff; padding: 12mm; display: flex; flex-direction: column; }
        .poster-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6mm; }
        .poster-type { font-size: 34px; font-weight: 800; letter-spacing: -.02em; line-height: 1; }
        .poster-type.lost { color: var(--lost-text); }
        .poster-type.found { color: var(--found-text); }
        .poster-photo { width: 100%; aspect-ratio: 4 / 3; border-radius: 12px; overflow: hidden; background: var(--paper); border: 1px solid var(--line); }
        .poster-photo img { width: 100%; height: 100%; object-fit: cover; }
        .poster h1 { font-size: 26px; margin: 6mm 0 2mm; }
        .poster-meta { font-size: 15px; color: var(--ink-2); }
        .poster-foot { margin-top: auto; display: flex; gap: 6mm; align-items: center; border-top: 2px solid var(--ink); padding-top: 5mm; }
        .poster-foot .url { font-size: 18px; font-weight: 800; }
        /* Story 1080x1920 (diperkecil di layar, diunduh ukuran penuh) */
        .story-scale { width: 270px; height: 480px; overflow: hidden; margin: 0 auto 3rem; border-radius: 16px; box-shadow: 0 6px 20px rgba(0,0,0,.15); }
        .story { width: 1080px; height: 1920px; transform: scale(.25); transform-origin: 0 0; background: var(--paper); padding: 90px 80px; display: flex; flex-direction: column; font-family: var(--bs-body-font-family); }
        .story .st-brand { display: flex; align-items: center; gap: 22px; font-size: 44px; font-weight: 800; color: var(--ink); }
        .story .st-brand .brand-mark { width: 84px; height: 84px; border-radius: 24px; font-size: 44px; }
        .story .st-type { margin-top: 70px; font-size: 120px; font-weight: 800; letter-spacing: -.03em; line-height: 1; }
        .story .st-type.lost { color: var(--lost-text); }
        .story .st-type.found { color: var(--found-text); }
        .story .st-photo { margin-top: 50px; width: 920px; height: 920px; border-radius: 48px; overflow: hidden; border: 14px solid #fff; box-shadow: 0 0 0 5px var(--ink); }
        .story .st-photo img { width: 100%; height: 100%; object-fit: cover; }
        .story .st-title { margin-top: 60px; font-size: 68px; font-weight: 800; line-height: 1.15; color: var(--ink); }
        .story .st-meta { margin-top: 20px; font-size: 40px; color: var(--ink-2); }
        .story .st-foot { margin-top: auto; display: flex; align-items: center; gap: 40px; }
        .story .st-foot .url { font-size: 44px; font-weight: 800; color: var(--ink); }
        .story .st-foot .hint { font-size: 32px; color: var(--muted); }
        @media print {
            @page { size: A5 portrait; margin: 0; }
            body { background: #fff; }
            .toolbar, .story-wrap { display: none !important; }
            .poster { margin: 0; width: 148mm; height: 210mm; }
        }
    </style>
</head>
<body>
    @php($typeText = $report->isLost() ? 'DICARI' : 'DITEMUKAN')

    <div class="toolbar">
        <a href="{{ route('reports.show', $report) }}" class="btn btn-outline-ink btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
        <button class="btn btn-ink btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak poster A5</button>
        <button class="btn btn-brand btn-sm" id="download-story"><i class="bi bi-download me-1"></i>Unduh gambar story</button>
    </div>

    <article class="poster">
        <div class="poster-head">
            <div class="poster-type {{ $report->type }}">{{ $typeText }}</div>
            <div class="brand"><span class="brand-mark"><i class="bi bi-geo-alt-fill"></i></span> FindIt</div>
        </div>
        <div class="poster-photo">
            @if ($report->photo_url)
                <img src="{{ $report->photo_url }}" alt="">
            @else
                <div class="photo-empty {{ $report->type }}" style="font-size:90px"><i class="bi bi-{{ $report->category->icon }}"></i></div>
            @endif
        </div>
        <h1>{{ $report->title }}</h1>
        <div class="poster-meta">
            <div><i class="bi bi-clock me-1"></i>{{ $report->event_at->translatedFormat('d F Y, H:i') }}</div>
            <div><i class="bi bi-geo-alt me-1"></i>{{ $report->place_name }}</div>
            <p class="mt-2 mb-0">{{ \Illuminate\Support\Str::limit($report->description, 180) }}</p>
        </div>
        <div class="poster-foot">
            <div id="qr-poster"></div>
            <div>
                <div class="small-caption">{{ $report->isLost() ? 'Melihat barang ini? Pindai kode QR' : 'Milikmu? Pindai lalu ajukan klaim' }}</div>
                <div class="url">{{ preg_replace('#^https?://#', '', $report->share_url) }}</div>
                <div class="small-caption">FindIt Campus · Telkom University</div>
            </div>
        </div>
    </article>

    <div class="story-wrap">
        <p class="text-center small-caption mb-2">Pratinjau story (1080×1920)</p>
        <div class="story-scale">
            <div class="story" id="story">
                <div class="st-brand"><span class="brand-mark"><i class="bi bi-geo-alt-fill"></i></span> FindIt Campus</div>
                <div class="st-type {{ $report->type }}">{{ $typeText }}</div>
                <div class="st-photo">
                    @if ($report->photo_url)
                        <img src="{{ $report->photo_url }}" alt="">
                    @else
                        <div class="photo-empty {{ $report->type }}" style="font-size:300px"><i class="bi bi-{{ $report->category->icon }}"></i></div>
                    @endif
                </div>
                <div class="st-title">{{ $report->title }}</div>
                <div class="st-meta">{{ $report->event_at->translatedFormat('d M Y, H:i') }} · {{ $report->campusLocation?->short_name ?? $report->place_name }}</div>
                <div class="st-foot">
                    <div id="qr-story" style="background:#fff;padding:16px;border-radius:20px"></div>
                    <div>
                        <div class="url">{{ preg_replace('#^https?://#', '', $report->share_url) }}</div>
                        <div class="hint">Pindai untuk lihat detail</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/qrcodejs/qrcode.min.js') }}"></script>
    <script src="{{ asset('vendor/html2canvas/html2canvas.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            const url = "{{ $report->share_url }}";
            new QRCode(document.getElementById('qr-poster'), { text: url, width: 110, height: 110 });
            new QRCode(document.getElementById('qr-story'), { text: url, width: 220, height: 220 });

            // Render elemen story ukuran penuh menjadi PNG lalu unduh
            $('#download-story').on('click', function () {
                const btn = $(this).prop('disabled', true).text('Menyiapkan…');
                const story = document.getElementById('story');
                const clone = story.cloneNode(true);
                clone.style.transform = 'none';
                clone.style.position = 'fixed';
                clone.style.left = '-10000px';
                clone.style.top = '0';
                document.body.appendChild(clone);

                html2canvas(clone, { width: 1080, height: 1920, scale: 1, backgroundColor: '#FAF8F5' }).then(function (canvas) {
                    const link = document.createElement('a');
                    link.download = 'findit-{{ $report->share_code }}-story.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                    document.body.removeChild(clone);
                    btn.prop('disabled', false).html('<i class="bi bi-download me-1"></i>Unduh gambar story');
                });
            });
        });
    </script>
</body>
</html>

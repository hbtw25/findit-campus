@extends('layouts.app')

@section('title', $title)

@section('styles')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <h1 class="page-title">{{ $title }}</h1>
        <p class="page-sub">Isi selengkap mungkin supaya pencocokan otomatis lebih akurat.</p>

        @if ($errors->any())
            <div class="flash flash-error mb-3"><i class="bi bi-exclamation-circle-fill"></i><div>Ada {{ $errors->count() }} isian yang perlu diperbaiki.</div></div>
        @endif

        {{-- Satu form untuk tambah dan ubah, seperti form.blade.php di Modul 12 --}}
        <form method="POST" action="{{ $route }}" enctype="multipart/form-data" id="report-form" novalidate>
            @csrf
            @if ($method === 'PUT')
                @method('PUT')
            @endif

            @php($type = old('type', $report->type))

            <div class="section-card">
                <div class="section-title">Jenis laporan</div>
                <div class="type-choice">
                    <input type="radio" name="type" id="type-lost" value="lost" @checked($type === 'lost')>
                    <label for="type-lost"><i class="bi bi-search" style="color: var(--lost)"></i><span>Saya kehilangan<small>Cari barang milikku</small></span></label>
                    <input type="radio" name="type" id="type-found" value="found" @checked($type === 'found')>
                    <label for="type-found"><i class="bi bi-check-circle" style="color: var(--found)"></i><span>Saya menemukan<small>Kembalikan ke pemiliknya</small></span></label>
                </div>
                @error('type')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="section-card">
                <div class="section-title">Data barang</div>
                <div class="mb-3">
                    <label for="title" class="form-label required">Judul</label>
                    <input type="text" name="title" id="title" maxlength="80" class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title', $report->title) }}" placeholder="Contoh: Dompet kulit coklat">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="category_id" class="form-label required">Kategori</label>
                        <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror">
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $report->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="color" class="form-label">Warna</label>
                        <input type="text" name="color" id="color" class="form-control @error('color') is-invalid @enderror" value="{{ old('color', $report->color) }}" placeholder="Coklat">
                        @error('color')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="brand" class="form-label">Merek</label>
                        <input type="text" name="brand" id="brand" class="form-control @error('brand') is-invalid @enderror" value="{{ old('brand', $report->brand) }}" placeholder="Opsional">
                        @error('brand')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label required">Deskripsi</label>
                    <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror"
                              placeholder="Ciri-ciri barang, isi, kondisi, dan di mana tepatnya.">{{ old('description', $report->description) }}</textarea>
                    <div class="form-text"><span id="desc-count">0</span> karakter · minimal 20</div>
                    @error('description')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-1">
                    <label for="event_at" class="form-label required" data-label-lost="Waktu hilang" data-label-found="Waktu ditemukan">Waktu kejadian</label>
                    <input type="datetime-local" name="event_at" id="event_at" class="form-control @error('event_at') is-invalid @enderror"
                           value="{{ old('event_at', $report->event_at?->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}">
                    @error('event_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="section-card">
                <div class="section-title">Foto barang <span class="small-caption fw-normal" id="photo-hint"></span></div>
                <label class="upload-box @error('photo') border-danger @enderror" for="photo">
                    <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/webp">
                    <img id="photo-preview" src="{{ $report->photo_url }}" alt="Pratinjau foto" @if (! $report->photo_url) style="display:none" @endif>
                    <div class="text-start">
                        <div class="fw-bold"><i class="bi bi-camera me-1"></i><span id="photo-label">{{ $report->photo_url ? 'Ganti foto' : 'Pilih atau ambil foto' }}</span></div>
                        <div class="small-caption">JPG, PNG, atau WEBP, maksimal 4 MB. Hindari memotret data pribadi seperti nomor KTM dengan jelas.</div>
                    </div>
                </label>
                @error('photo')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="section-card">
                <div class="section-title">Lokasi</div>
                <div class="row g-3 mb-3">
                    <div class="col-md-7">
                        <label for="campus_location_id" class="form-label">Gedung terdekat</label>
                        <select name="campus_location_id" id="campus_location_id" class="form-select @error('campus_location_id') is-invalid @enderror">
                            <option value="">Pilih gedung (peta akan bergeser)</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" data-lat="{{ $location->lat }}" data-lng="{{ $location->lng }}"
                                    @selected(old('campus_location_id', $report->campus_location_id) == $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="location_note" class="form-label">Keterangan</label>
                        <input type="text" name="location_note" id="location_note" class="form-control" maxlength="120"
                               value="{{ old('location_note', $report->location_note) }}" placeholder="Lantai 2, dekat tangga">
                    </div>
                </div>
                <div class="pick-map" id="pick-map"></div>
                <div class="d-flex justify-content-between mt-2 small-caption">
                    <span><i class="bi bi-hand-index me-1"></i>Klik peta atau geser pin ke titik persis.</span>
                    <span id="coord-text"></span>
                </div>
                <input type="hidden" name="lat" id="lat" value="{{ old('lat', $report->lat) }}">
                <input type="hidden" name="lng" id="lng" value="{{ old('lng', $report->lng) }}">
                @error('lat')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="section-card secret-section">
                <div class="secret-box">
                    <label for="secret_detail" class="form-label required"><i class="bi bi-shield-lock me-1"></i>Ciri rahasia</label>
                    <input type="text" name="secret_detail" id="secret_detail" maxlength="255" class="form-control @error('secret_detail') is-invalid @enderror"
                           value="{{ old('secret_detail', $method === 'PUT' ? $report->secret_detail : '') }}" placeholder="Contoh: ada stiker inisial S.N. di bagian bawah">
                    <div class="form-text">Hanya admin yang melihat. Dipakai untuk memverifikasi klaim. Jangan tulis ciri ini di deskripsi.</div>
                    @error('secret_detail')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <p class="small-caption mt-3"><i class="bi bi-info-circle me-1"></i>Deskripsi dan foto dapat diproses AI untuk pencocokan otomatis. Ciri rahasia tidak pernah dikirim ke AI.</p>

            <div class="form-actions mt-3 mb-5">
                <a href="{{ $method === 'PUT' ? route('reports.show', $report) : route('map') }}" class="btn btn-outline-ink">Batal</a>
                <button type="submit" class="btn btn-brand px-4">{{ $method === 'PUT' ? 'Simpan Perubahan' : 'Kirim Laporan' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script>
        window.FINDIT_PICKER = {
            center: [{{ $campus['lat'] }}, {{ $campus['lng'] }}],
            radius: {{ $campus['radius_m'] }},
        };
    </script>
    <script src="{{ asset('js/report-form.js') }}"></script>
@endsection

# Tech Stack dan Gaya Kode FindIt Campus

Dokumen ini menjelaskan teknologi yang dipakai, alasannya, dan aturan penulisan kode. Semua pilihan sengaja mengikuti materi Pemrograman Web (slide minggu 1 sampai 13 dan Modul Praktikum 2 sampai 13), supaya setiap anggota kelompok bisa membaca dan melanjutkan kodenya.

Kode sumber: `C:\laragon\www\findit-campus` (folder standar proyek Laragon).

## 1. Tech Stack

| Lapisan | Teknologi | Versi | Materi terkait | Dipakai untuk |
|---|---|---|---|---|
| Backend | Laravel (PHP framework, MVC) | 13.x di PHP 8.3 | Slide 10 sampai 13, Modul 11 sampai 13 | Routing, controller, Eloquent, Blade, Auth, validasi |
| Database | MySQL | 8.4 (Laragon) | Slide 1 (DBMS), Modul 12 | 8 tabel aplikasi + tabel bawaan Laravel |
| Template | Blade | bawaan Laravel | Modul 11 sampai 12 (`@extends`, `@yield`, `@csrf`, `@error`) | Semua halaman |
| CSS framework | Bootstrap | 5.3.3 | Slide 4, Modul 5 | Grid, form, tombol, modal, dropdown |
| CSS kustom | `public/css/app.css` | - | Slide 3, Modul 4 (selector, box model, flexbox/grid, `@media`, `@font-face`) | Design token, marker foto, layout peta |
| JavaScript | jQuery | 3.7.1 | Slide 5, Modul 6 | DOM, event, `$.ajax` |
| AJAX + JSON | jQuery `$.ajax` + `response()->json()` | - | Slide 9, Modul 10 | Peta, notifikasi, chat, tabel admin |
| Plugin jQuery | DataTables | 2.1.8 | Rubrik COTS ("JQuery Plugin", "JSON untuk datatable") | Tabel admin |
| Peta (GIS) | Leaflet + Leaflet.markercluster + tile OpenStreetMap | 1.9.4 / 1.5.3 | Tidak ada di materi; sama dengan peta whereis.telkomuniversity.ac.id | Peta Global, pemilih lokasi, peta mini |
| QR & story | qrcode.js, html2canvas | 1.0.0 / 1.4.1 | - | Fitur bagikan |
| AI (opsional) | Claude API via SDK resmi `anthropic-ai/sdk` | 1.8 | - | Penilaian ulang auto-match (teks + foto) |
| Ikon & font | Bootstrap Icons, Plus Jakarta Sans | 1.11.3 / variable | - | Antarmuka |
| Server lokal | Laragon (Apache/MySQL) atau `php artisan serve` | - | Slide 1 (XAMPP/Laragon), Modul 9 | Menjalankan aplikasi |
| Versi kode | Git + GitHub | - | Modul 2 | Kerja kelompok |
| Pengujian | PHPUnit (tes fitur Laravel) | 12 | - | 41 tes otomatis |

Semua library frontend disimpan di `public/vendor/` (bukan CDN). Dengan begitu, demo tetap berjalan meski internet kampus lambat. Hanya tile peta OSM yang butuh internet.

## 2. Arsitektur MVC

```
Browser ──HTTP──▶ routes/web.php ──▶ Middleware (auth / admin / throttle)
                                          │
                                          ▼
                                   Controller (app/Http/Controllers)
                                   ├─ validasi $request->validate([...])
                                   ├─ Model Eloquent (app/Models) ──▶ MySQL
                                   ├─ Service (app/Services): MatchService, ClaimFlow, PhotoService, AiMatcher
                                   └─ return view('...', $data)     ← Server-side rendering (Blade)
                                      atau response()->json($data)  ← Client-side rendering (jQuery AJAX)
```

Aplikasi memakai dua cara render yang dibahas di slide minggu 1. Halaman form, detail, daftar, dan admin dirender di server oleh Blade (server-side rendering). Marker Peta Global, daftar "Di sekitar sini", lonceng notifikasi, chat, dan tabel DataTables dibangun di browser dari JSON (client-side rendering), yang menjadi poin bonus pada penilaian proyek akhir.

### Struktur folder penting

```
app/
  Http/Controllers/        AuthController, MapController, ReportController, ClaimController,
                           MessageController, AlertController, MatchController, ShareController
  Http/Controllers/Admin/  DashboardController, ReportController, ClaimController, UserController, CategoryController
  Http/Middleware/         EnsureUserIsAdmin (alias 'admin')
  Models/                  User, Category, CampusLocation, Report, ReportMatch, Claim, Message, Alert
  Services/                MatchService (skor aturan), AiMatcher (Claude), ClaimFlow (status klaim), PhotoService (GD)
  Jobs/                    ScoreMatchesWithAi (antrian)
config/findit.php          Pusat kampus, radius, batas laporan, ambang skor, API key AI
database/migrations/       Skema 8 tabel
database/seeders/          Kategori, 34 gedung kampus, akun demo, 20 laporan demo + klaim + chat
resources/views/           layouts/, partials/, auth/, map/, reports/, claims/, alerts/, share/, admin/, errors/
public/css/app.css         Design token + komponen
public/js/                 app.js, map.js, report-form.js, report-show.js, chat.js, admin-tables.js
tests/Feature/             AuthTest, ReportTest, MatchServiceTest, ClaimFlowTest
```

## 3. Gaya Kode (ikuti ini saat menambah fitur)

### 3.1 Routing (seperti Modul 11)
```php
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::resource('categories', AdminCategoryController::class)->except('show');
Route::middleware('auth')->group(function () { ... });
```
- Selalu beri `->name()` dan panggil URL dengan `route('nama')`, jangan menulis URL manual.
- Endpoint JSON diawali `/ajax/...` (admin: `/admin/ajax/...`).

### 3.2 Controller (pola resource seperti `ProductController` di Modul 12)
```php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|min:3|max:40|unique:categories,name',
        'icon' => 'required|string|max:40',
    ]);

    Category::create($validated);

    return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil ditambahkan');
}
```
- Validasi ditulis langsung di controller dengan aturan berformat string `'required|min:4'`.
- Setelah simpan/ubah/hapus: `redirect()->route(...)->with('success', '...')` (flash session, Modul 13).
- Tambah dan ubah memakai **satu view `form.blade.php`** dengan variabel `$title`, `$route`, `$method`.
- Logika yang dipakai di beberapa controller dipindah ke `app/Services` (contoh: `ClaimFlow`), supaya controller tetap pendek.

### 3.3 Model: Eloquent seperti Modul 12 sampai 13
```php
class Report extends Model
{
    protected $fillable = ['user_id', 'category_id', 'type', 'title', ...];
    protected $hidden = ['secret_detail'];        // tidak ikut ke JSON

    public function user() { return $this->belongsTo(User::class); }
    public function claims() { return $this->hasMany(Claim::class); }
}
```
- Pakai `protected $fillable` (bukan atribut PHP `#[Fillable]` bawaan Laravel 13), supaya sama dengan modul.
- Relasi `hasMany` / `belongsTo`; gunakan eager loading `with('category')` di daftar agar tidak terjadi query N+1.
- Nilai bawaan kolom ditulis juga di `$attributes`, supaya model yang baru dibuat langsung punya status yang benar.

### 3.4 View: Blade seperti Modul 12
```blade
@extends('layouts.app')
@section('title', 'Jelajah Laporan')
@section('content')
    <form method="POST" action="{{ $route }}">
        @csrf
        @if ($method === 'PUT') @method('PUT') @endif
        <input name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $report->title) }}">
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </form>
@endsection
@section('scripts') <script src="{{ asset('js/nama-halaman.js') }}"></script> @endsection
```
- Aset selalu lewat `{{ asset('...') }}`.
- Teks antarmuka berbahasa Indonesia; nama tabel, kolom, kelas, dan variabel berbahasa Inggris (seperti `products`, `name`, `price` di modul).
- Komponen yang berulang dijadikan `partials/` (navbar, flash, kartu laporan, label jenis).

### 3.5 JavaScript: jQuery seperti Modul 6 dan Slide 9
```js
$(document).ready(function () {
    $.ajax({
        type: 'GET',
        url: '/ajax/map/reports',
        data: { type: 'found' },
        dataType: 'json',
        success: function (rsp) { /* render marker */ },
        error: function () { /* tampilkan pesan */ },
    });
});
```
- Satu file JS per halaman di `public/js/`; data dari server dikirim lewat objek `window.FINDIT_...` di Blade.
- Token CSRF dipasang sekali di `app.js` dengan `$.ajaxSetup`.
- Teks dari pengguna yang dimasukkan ke HTML wajib lewat `escapeHtml()` (mencegah XSS).
- Elemen yang dibuat dinamis memakai event delegation: `$(document).on('click', '.list-row', ...)` (seperti contoh slide LabBook).

### 3.6 CSS
- Warna, radius, dan bayangan memakai variabel di `:root` (`var(--brand)`, `var(--lost)`, `var(--found)`) sesuai design token PRD §12.
- Utamakan kelas Bootstrap; kelas kustom hanya untuk yang tidak ada di Bootstrap (marker foto, chip, timeline).
- Jenis laporan tidak boleh dibedakan dengan warna saja: selalu sertakan ikon atau teks.

### 3.7 Komentar dan format
- Komentar singkat dalam bahasa Indonesia, hanya untuk menjelaskan **alasan** (bukan mengulang kode).
- PHP mengikuti PSR-12 (rapikan dengan `vendor/bin/pint`), indentasi 4 spasi.
- Commit Git kecil dan jelas, misalnya `feat: form klaim kepemilikan` atau `fix: filter kategori di peta`.

## 4. Daftar Endpoint AJAX (JSON)

| Method | URL | Akses | Isi respons |
|---|---|---|---|
| GET | `/ajax/map/reports?type=&category=&days=&q=` | Publik | `{count, data: [ {id, type, title, lat, lng, thumb, place, time_ago, url, ...} ]}` |
| GET | `/ajax/campus-locations` | Publik | 34 titik gedung |
| GET | `/ajax/alerts` | Login | `{unread, data: [6 notifikasi terbaru]}` |
| GET | `/ajax/claims/{id}/messages?after=ID` | Penemu, pengklaim, admin | Pesan baru setelah ID tertentu |
| POST | `/ajax/claims/{id}/messages` | Sama (hanya setelah klaim disetujui) | Pesan yang baru dibuat (201) |
| GET | `/admin/ajax/reports`, `/admin/ajax/claims`, `/admin/ajax/users` | Admin | `{data: [...]}` untuk DataTables |

## 5. Integrasi AI

Penilaian AI ada di `app/Services/AiMatcher.php` dan dijalankan oleh job `app/Jobs/ScoreMatchesWithAi.php`. Untuk mengaktifkannya, isi `ANTHROPIC_API_KEY` di `.env` lalu jalankan `php artisan queue:work` di terminal kedua. Tanpa key, aplikasi tetap memakai skor aturan.

| Aspek | Keterangan |
|---|---|
| Model | `claude-opus-5-5` (bisa diganti lewat `ANTHROPIC_MODEL`), `effort: low` karena tugasnya penilaian singkat |
| Format keluaran | JSON lewat structured output (`outputConfig.format = json_schema`), jadi hasilnya selalu bisa di-parse |
| Privasi | Ciri rahasia tidak dikirim; foto yang dikirim hanya thumbnail 320 px |
| Ketahanan | Error API, timeout, dan penolakan (`stopReason = refusal`) ditangkap, lalu skor aturan tetap dipakai. Fallback model di sisi server belum dipasang karena penolakan untuk penilaian barang hilang sangat jarang |
| Biaya | Sekitar US$0,05 per pencocokan (1 laporan hilang dan maksimal 5 kandidat bergambar) |

## 6. Menjalankan Proyek

Lihat `README.md` di folder proyek. Singkatnya:
1. Buka Laragon dan tekan Start All (MySQL harus hidup).
2. Jalankan:
   ```
   cd C:\laragon\www\findit-campus
   composer install
   php artisan migrate:fresh --seed
   php artisan storage:link
   php artisan serve
   ```
3. Buka http://127.0.0.1:8000.
4. Akun demo ada di README (semua kata sandi `findit123`).
5. Tes otomatis: `php artisan test`.

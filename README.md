# FindIt Campus

Web pencarian barang hilang berbasis peta untuk Telkom University. Tugas Besar Pemrograman Web, Kelompok 4.

- **Peta Global:** setiap laporan tampil sebagai foto barang di atas peta kampus (Leaflet + OpenStreetMap), mirip peta lokasi foto di Instagram.
- **Auto-match:** laporan hilang dan laporan temuan dicocokkan otomatis (kategori, jarak, waktu, kemiripan teks). Bila API key Gemini diisi, AI ikut menilai ulang dengan membaca foto, dan notifikasi ditahan sampai penilaian AI selesai.
- **Klaim aman:** pengklaim menjawab ciri rahasia, admin memverifikasi, lalu serah terima diatur lewat chat.

Dokumen lengkap ada di folder `docs/`: PRD, tech stack dan gaya kode, roadmap, desain FigCraft (`docs/design`), laporan Tubes (`docs/laporan`), dan manual book untuk pengguna dan admin (`docs/manual`).

## Menjalankan di Laptop (Laragon)

1. Buka Laragon, tekan **Start All** (MySQL harus menyala).
2. Buat database `findit_campus` lewat HeidiSQL/phpMyAdmin, atau biarkan langkah berikut membuatnya.
3. Di terminal Laragon:
   ```bash
   cd C:\laragon\www\findit-campus
   composer install
   copy .env.example .env      # hanya bila .env belum ada
   php artisan key:generate    # hanya bila .env baru dibuat
   php artisan migrate:fresh --seed
   php artisan storage:link
   php artisan serve
   ```
4. Buka **http://127.0.0.1:8000**.

### Akun demo (semua kata sandi `findit123`)

| Peran | Email | Isi demo |
|---|---|---|
| Admin | admin@telkomuniversity.ac.id | 1 klaim menunggu verifikasi (dompet milik Bagas) |
| Pengguna | dimas@student.telkomuniversity.ac.id | Kunci motor hilang, sudah ada kandidat temuan yang cocok |
| Pengguna | rani@student.telkomuniversity.ac.id | Penemu kunci motor, payung, flashdisk |
| Pengguna | salsa@student.telkomuniversity.ac.id | Penemu headphone (klaim disetujui + chat) |
| Pengguna | nadia@student.telkomuniversity.ac.id | Pemilik headphone (chat dengan Salsa) |
| Pengguna | bagas@student.telkomuniversity.ac.id | Mengklaim dompet (menunggu) |

### Demo dengan HP (scan QR, tampilan mobile)

Jalankan `php artisan serve --host=0.0.0.0`. Buka `http://IP-LAPTOP:8000` dari HP yang tersambung ke Wi-Fi yang sama; IP laptop bisa dilihat dengan `ipconfig`. Kode QR di poster otomatis memakai alamat yang sedang dibuka.

### Sebelum presentasi

Aktifkan OPcache di `php.ini` Laragon (menu Laragon, PHP, php.ini): hapus titik koma di depan `zend_extension=opcache` dan `opcache.enable=1`, lalu ubah `opcache.enable_cli=0` menjadi `opcache.enable_cli=1`. Tanpa OPcache, Peta Global dengan 200 laporan butuh sekitar 2,2 detik; dengan OPcache sekitar 0,8 detik.

Ubah `APP_DEBUG=true` menjadi `APP_DEBUG=false` di `.env`, supaya bila terjadi error yang tampil adalah halaman ramah, bukan stack trace. Jalankan `php artisan migrate:fresh --seed` untuk mengembalikan data demo ke kondisi awal.

### Mengaktifkan penilaian AI (opsional)

1. Buat API key gratis di Google AI Studio (https://aistudio.google.com, menu Get API key).
2. Isi `GEMINI_API_KEY=` di `.env`. Model bawaan `gemini-3.5-flash-lite` (tier gratis, bisa membaca foto); bisa diganti lewat `GEMINI_MODEL`.
3. Jalankan antrian di terminal kedua, karena penilaian AI berjalan di belakang supaya pengguna tidak menunggu:
   ```bash
   php artisan queue:work
   ```
4. Jalankan `php artisan migrate:fresh --seed` bila ingin data demo ikut dinilai AI (sekitar 20 detik).

Tanpa API key atau bila kuota gratis habis, aplikasi tetap berjalan normal memakai skor aturan. OpenCode Console sudah disiapkan sebagai cadangan (`AI_PROVIDERS=gemini,opencode`, `OPENCODE_API_KEY`, `OPENCODE_MODEL`), tetapi model gratis OpenCode hanya bisa dipakai dari aplikasi OpenCode, jadi cadangan ini butuh model berbayar atau OpenCode Go.

Jangan pernah commit `.env`: file ini berisi API key dan sudah ada di `.gitignore`.

## Tes Otomatis

```bash
php artisan test
```
48 tes fitur memakai SQLite di memori, jadi tidak menyentuh database MySQL. Tes AI memakai `Http::fake()`, dan semua request HTTP sungguhan diblokir selama tes, jadi tes tidak menghabiskan kuota API.

## Teknologi

Laravel 13 (PHP 8.3) · MySQL 8 · Blade · Bootstrap 5.3 · jQuery 3.7 + AJAX/JSON · DataTables · Leaflet + MarkerCluster + OpenStreetMap · qrcode.js · html2canvas · Gemini API dari Google AI Studio (opsional, tier gratis).

Semua library frontend ada di `public/vendor` (tidak butuh CDN); hanya tile peta yang butuh internet.

## Struktur Singkat

| Folder | Isi |
|---|---|
| `app/Http/Controllers` | Controller pengguna + `Admin/` |
| `app/Models` | User, Category, CampusLocation, Report, ReportMatch, Claim, Message, Alert |
| `app/Services` | MatchService, AiMatcher, ClaimFlow, PhotoService |
| `config/findit.php` | Pusat kampus, radius, ambang skor, batas laporan |
| `database/seeders` | Data demo + 34 titik gedung kampus |
| `resources/views` | Blade per fitur |
| `public/js` | Satu skrip jQuery per halaman |
| `tests/Feature` | Tes login, laporan, auto-match, klaim |

Peta © kontributor OpenStreetMap. Titik gedung diambil dari peta publik whereis.telkomuniversity.ac.id.

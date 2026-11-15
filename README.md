# FindIt Campus

Web pencarian barang hilang berbasis peta untuk Telkom University. Tugas Besar Pemrograman Web, Kelompok 4.

- **Peta Global:** setiap laporan tampil sebagai foto barang di atas peta kampus (Leaflet + OpenStreetMap), mirip peta lokasi foto di Instagram.
- **Auto-match:** laporan hilang dan laporan temuan dicocokkan otomatis (kategori, jarak, waktu, kemiripan teks). Bila API key diisi, Claude ikut menilai ulang dengan membaca foto.
- **Klaim aman:** pengklaim menjawab ciri rahasia, admin memverifikasi, lalu serah terima diatur lewat chat.

Dokumen lengkap ada di folder `docs/`: PRD, tech stack dan gaya kode, roadmap, desain FigCraft (`docs/design`), dan laporan Tubes (`docs/laporan`).

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

1. Isi `ANTHROPIC_API_KEY=` di `.env` (bisa juga mengganti `ANTHROPIC_MODEL`).
2. Jalankan antrian di terminal kedua:
   ```bash
   php artisan queue:work
   ```

Tanpa API key, aplikasi tetap berjalan normal memakai skor aturan.

## Tes Otomatis

```bash
php artisan test
```
42 tes fitur memakai SQLite di memori, jadi tidak menyentuh database MySQL.

## Teknologi

Laravel 13 (PHP 8.3) · MySQL 8 · Blade · Bootstrap 5.3 · jQuery 3.7 + AJAX/JSON · DataTables · Leaflet + MarkerCluster + OpenStreetMap · qrcode.js · html2canvas · Claude API (opsional).

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

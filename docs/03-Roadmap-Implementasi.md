# Roadmap Implementasi FindIt Campus

Kondisi per **16 November 2026**: versi 1 sudah **berjalan penuh** di `C:\laragon\www\findit-campus`. Semua fitur F1 sampai F12 terimplementasi, 34 tes otomatis lulus, dan data demo siap untuk presentasi.

Roadmap ini dipakai kelompok untuk:
1. memahami kode bagian masing-masing (sesuai PIC);
2. merapikan dan menambah fitur pengayaan;
3. bersiap untuk Responsi Tubes 2 (Modul 14) dan presentasi akhir.

Asumsi jadwal: satu modul praktikum per minggu.

## 1. Kepemilikan Kode per PIC

Setiap anggota sebaiknya bisa menjelaskan file bagiannya saat presentasi (biasanya ditanya per individu).

| PIC | Fitur | File utama |
|---|---|---|
| Harsya | F1 Registrasi & Login, F5 Klaim, F11 AI Auto-Match | `AuthController`, `auth/*.blade.php`, `ClaimController`, `Services/ClaimFlow.php`, `claims/*.blade.php`, `Services/MatchService.php`, `Services/AiMatcher.php`, `Jobs/ScoreMatchesWithAi.php` |
| Zeifira | F2 Pelaporan, F6 Komunikasi, F12 Notifikasi | `ReportController` (create/store/edit/update/destroy), `reports/form.blade.php`, `js/report-form.js`, `MessageController`, `js/chat.js`, `AlertController`, `alerts/index.blade.php` |
| Alfi | F3 Pemetaan & Lokasi, F7 Admin | `CampusLocationSeeder` + `database/data/campus_locations.csv`, pemilih lokasi di `js/report-form.js`, peta mini `js/report-show.js`, `Admin/*Controller`, `admin/*.blade.php`, `js/admin-tables.js`, `Middleware/EnsureUserIsAdmin` |
| Fino | F4 Pencarian & Filter, F8 Integrasi & Pengujian | `Report::scopeFilter`, `reports/index.blade.php`, `MapController@reports`, `tests/Feature/*`, seeder demo, README |
| Faiz | F9 Visualisasi Foto di Peta, F10 Berbagi | `MapController`, `map/index.blade.php`, `js/map.js`, CSS marker foto (`.pm`, `.pc` di `app.css`), `ShareController`, `share/poster.blade.php`, modal bagikan |

## 2. Jadwal

| Minggu | Modul praktikum | Fokus kelompok | Luaran |
|---|---|---|---|
| 0 (15 sampai 16 Nov) | Modul 10 AJAX | ✅ PRD, desain FigCraft, website v1, tes, laporan | Semua dokumen + aplikasi berjalan |
| 1 (17 sampai 23 Nov) | Modul 11 Laravel Intro | Push ke GitHub (Modul 2), setiap anggota clone + jalankan lokal + membaca file PIC-nya; uji ulang koordinat 34 gedung di lapangan | Repo kelompok, semua laptop bisa menjalankan aplikasi |
| 2 (24 sampai 30 Nov) | Modul 12 Laravel DB 1 | Pengayaan pilihan (§4); foto demo diganti foto asli barang (opsional) | 1 sampai 2 fitur pengayaan |
| 3 (1 sampai 7 Des) | Modul 13 Laravel DB 2 | Uji pengguna dengan 3 teman (target < 3 menit per laporan); perbaiki temuan; aktifkan AI bila ada API key | Catatan uji pengguna |
| 4 (8 sampai 14 Des) | Modul 14 Responsi Tubes 2 | Asistensi dengan asprak (target progres 80 sampai 100% sudah tercapai), latihan demo | Demo lancar + slide |
| 5 (15 sampai 21 Des) | Modul 15 COTS | Persiapan coding on the spot: setiap anggota latihan membuat CRUD kecil dengan pola yang sama | - |

## 3. Status Fitur

| ID | Fitur | Status | Catatan |
|---|---|---|---|
| F1 | Registrasi & Login | ✅ | Validasi email kampus, throttle 5×/menit, akun nonaktif ditolak |
| F2 | Pelaporan Hilang & Ditemukan | ✅ | CRUD + foto (GD: 1280 px + thumbnail 320 px) + tutup/selesai |
| F3 | Pemetaan & Lokasi | ✅ | Pilih gedung lalu geser pin, validasi radius kampus 1,5 km |
| F4 | Pencarian & Filter | ✅ | Kata kunci, jenis, kategori, gedung, periode; dipakai juga di peta |
| F5 | Klaim Kepemilikan | ✅ | Ciri rahasia, maksimal 3 klaim menunggu, tidak bisa klaim laporan sendiri |
| F6 | Komunikasi & Pengembalian | ✅ | Chat AJAX (polling 5 detik), konfirmasi diserahkan |
| F7 | Admin | ✅ | Dashboard, DataTables JSON, verifikasi klaim berdampingan, CRUD kategori |
| F8 | Integrasi & Pengujian | ✅ | 34 tes fitur lulus, seeder demo |
| F9 | Peta Foto ala Instagram | ✅ | Marker foto + cluster foto terbaru + jumlah |
| F10 | Bagikan Link/QR/Poster | ✅ | `/r/{kode}`, QR, poster A5 (print), unduh story 1080×1920 |
| F11 | AI Auto-Match | ✅ | Skor aturan selalu jalan; Claude menilai ulang bila API key diisi |
| F12 | Notifikasi | ✅ | Lonceng AJAX + halaman notifikasi + kandidat cocok |

## 4. Ide Pengayaan (bila ada waktu)

1. Pencari bisa menyimpan filter pencarian, lalu mendapat notifikasi saat ada laporan baru yang cocok.
2. Admin mendapat grafik laporan per minggu (Chart.js) dan peta panas lokasi yang sering kehilangan barang.
3. Laporan yang lebih dari 60 hari tanpa aktivitas ditutup otomatis oleh Laravel scheduler.
4. Admin bisa mengekspor rekap barang kembali per bulan ke CSV untuk Kemahasiswaan.
5. Satu laporan bisa memuat beberapa foto (tabel `report_photos`).

## 5. Skenario Uji End-to-End

Semua skenario di bawah sudah diuji manual di browser dan/atau lewat tes otomatis pada 16 Nov 2026.

| No | Skenario | Hasil yang diharapkan | Status |
|---|---|---|---|
| 1 | Daftar dengan email @gmail.com | Ditolak: "Gunakan email kampus…" | ✅ tes otomatis |
| 2 | Login dengan sandi salah | Pesan error, tetap di halaman login | ✅ tes otomatis |
| 3 | Login sebagai pengguna demo | Masuk ke Peta Global, lonceng menampilkan jumlah notifikasi | ✅ browser |
| 4 | Buka Peta Global | Foto barang tampil di peta, cluster menunjukkan jumlah | ✅ browser |
| 5 | Filter "Ditemukan" + kategori | Marker dan daftar berubah tanpa reload | ✅ browser |
| 6 | Buat laporan temuan tanpa foto | Error "Foto wajib untuk laporan barang temuan." | ✅ tes otomatis |
| 7 | Pin di luar kampus | Error "Titik lokasi harus berada di area kampus" | ✅ tes otomatis |
| 8 | Buat laporan temuan yang mirip laporan hilang | Kandidat muncul, pemilik laporan hilang mendapat notifikasi | ✅ browser + tes |
| 9 | Ajukan klaim dari kandidat | Status Menunggu, laporan menjadi Proses Klaim | ✅ browser + tes |
| 10 | Admin tolak tanpa alasan | Ditolak validasi (min. 10 karakter) | ✅ tes otomatis |
| 11 | Admin setujui klaim | Klaim lain otomatis ditolak, chat terbuka | ✅ browser + tes |
| 12 | Kirim pesan chat | Pesan muncul tanpa reload | ✅ browser |
| 13 | Penemu konfirmasi diserahkan | Laporan temuan dan laporan hilang menjadi Selesai | ✅ tes otomatis |
| 14 | Pengguna biasa membuka /admin | 403 "Halaman ini khusus admin." | ✅ tes otomatis |
| 15 | Buka poster lalu "Unduh gambar story" | PNG 1080×1920 terunduh | ✅ browser |
| 16 | Pengguna lain mengklaim barang yang klaimnya sudah disetujui | Ditolak: "sedang dalam proses serah terima" | ✅ tes otomatis |
| 17 | Mengklaim ulang setelah klaim ditolak | Ditolak, pengguna diminta menghubungi admin | ✅ tes otomatis |
| 18 | Pelapor menutup laporan saat klaim sudah disetujui | Ditolak; admin yang menutup akan membatalkan semua klaim berjalan | ✅ tes otomatis |
| 19 | Admin menonaktifkan akun yang sedang login | Pengguna langsung keluar pada klik berikutnya | ✅ tes otomatis |
| 20 | Mengirim chat setelah barang diserahkan | Ditolak (403), riwayat chat tetap bisa dibaca | ✅ tes otomatis |

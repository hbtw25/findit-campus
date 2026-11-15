# PRD FindIt Campus

**Pencarian Barang Hilang Berbasis Peta untuk Telkom University**
Versi 1.0 · 15 November 2026 · Tugas Besar Pemrograman Web · Kelompok 4

| Peran dokumen | Isi |
|---|---|
| Pemilik produk | Kelompok 4 (Harsya, Alfi, Zeifira, Fino, Faiz) |
| Status | Disetujui untuk implementasi v1 |
| Dokumen terkait | `02-Tech-Stack-dan-Gaya-Kode.md`, `03-Roadmap-Implementasi.md`, desain FigCraft di `../design/` |

> FindIt Campus adalah web untuk melaporkan barang hilang dan barang temuan di kampus Telkom University Bandung. Inti produknya **Peta Global**: setiap laporan muncul sebagai foto barang di atas peta kampus (OpenStreetMap, seperti peta *whereis* Telkom), mirip peta lokasi foto di Instagram. Saat ada laporan baru, sistem **otomatis mencocokkan** laporan hilang dengan laporan temuan (berdasarkan kategori, jarak, waktu, dan kemiripan deskripsi, lalu dinilai ulang oleh AI bila diaktifkan), dan mengirim notifikasi *"diduga cocok"* ke pemilik barang. Pemilik mengajukan klaim dengan menjawab ciri rahasia barang, admin memverifikasi, lalu serah terima diatur lewat chat.

---

## 1. Latar Belakang dan Masalah

Di lingkungan kampus, kehilangan dompet, KTM, kunci motor, botol minum, atau charger laptop terjadi hampir setiap hari. Saat ini informasinya tersebar di grup WhatsApp angkatan, story Instagram, dan papan pengumuman gedung. Akibatnya:

1. Info barang tenggelam di chat dan tidak bisa disaring berdasarkan jenis barang, waktu, atau gedung.
2. Penemu menulis "di dekat kantin" tanpa titik yang pasti, padahal kampus punya puluhan gedung.
3. Pemilik sering tidak tahu barangnya sudah ditemukan, karena tidak ada yang mempertemukan laporan kehilangan dengan laporan temuan.
4. Siapa saja bisa mengaku sebagai pemilik, karena foto barang sudah tersebar luas.

## 2. Tujuan dan Indikator Keberhasilan

**Tujuan:** pelaporan, pencarian, pencocokan, klaim, dan serah terima barang berlangsung di satu portal berbasis peta.

| Indikator | Target | Cara ukur |
|---|---|---|
| Waktu membuat satu laporan | < 3 menit | Uji 3 pengguna dengan stopwatch |
| Laporan baru tampil di Peta Global | < 3 detik setelah dikirim | Tampil saat filter/peta dimuat ulang via AJAX |
| Akurasi auto-match | Barang yang benar ada di 3 kandidat teratas pada ≥ 8 dari 10 pasangan uji | Skenario uji pasangan hilang dan temuan |
| Waktu muat Peta Global | < 2 detik untuk 200 laporan | DevTools Network, marker di-cluster |
| Skenario uji end-to-end | 100% lulus sebelum Responsi Tubes 2 | Tabel skenario di Roadmap §5 |

## 3. Persona dan Hak Akses

Proposal menyebut tiga peran: **Pelapor**, **Pencari Barang**, dan **Admin**. Dalam praktiknya satu mahasiswa bisa menjadi pelapor sekaligus pencari, jadi di sistem hanya ada **dua jenis akun**: `user` (menjalankan peran Pelapor dan Pencari) dan `admin`.

| Peran | Profil contoh | Kebutuhan utama | Perangkat | Hak akses |
|---|---|---|---|---|
| Pelapor (akun `user`) | Rani, 20 th, menemukan dompet di KU3 | Melapor cepat dari HP, menandai lokasi persis | HP | Buat/ubah/tutup laporan sendiri, lihat kandidat cocok, konfirmasi serah terima |
| Pencari (akun `user`) | Dimas, 21 th, kehilangan kunci motor | Tahu kalau barangnya sudah ditemukan orang | HP | Jelajah peta, filter, ajukan klaim, chat setelah klaim disetujui |
| Admin | Petugas Kemahasiswaan / Lost & Found | Memverifikasi klaim, menjaga laporan tetap rapi | Laptop | Moderasi laporan, setujui/tolak klaim, kelola pengguna dan kategori |
| Tamu (belum login) | Siapa saja yang membuka tautan bagikan | Melihat barang di peta | HP | Lihat peta dan detail publik; aksi lain wajib login |

## 4. Fitur, User Story, dan Kriteria Penerimaan

Nomor fitur F1 sampai F10 mengikuti proposal Tubes beserta PIC-nya. F11 sampai F12 adalah tambahan baru, dan PIC-nya masih **usulan yang perlu disepakati kelompok**.

| ID | Fitur (PIC) | User story | Kriteria penerimaan | Prioritas |
|---|---|---|---|---|
| F1 | Registrasi & Login (Harsya) | Sebagai mahasiswa, saya ingin masuk dengan akun kampus agar laporan saya terpercaya | Registrasi: nama, NIM/NIP, email kampus, sandi ≥ 8 karakter; login email + sandi via `Auth::attempt`; 5 kali gagal per menit diblokir sementara; akun nonaktif tidak bisa login; logout | Must |
| F2 | Pelaporan Hilang & Ditemukan (Zeifira) | Sebagai pelapor, saya ingin membuat laporan lengkap dengan foto agar mudah dikenali | Form satu halaman: jenis, judul, kategori, warna, merek, deskripsi, waktu, foto, lokasi; validasi server + pesan `@error`; ubah, hapus (jika belum ada klaim), tutup laporan | Must |
| F3 | Pemetaan & Penentuan Lokasi (Alfi) | Sebagai pelapor, saya ingin menandai titik di peta kampus agar lokasinya jelas | Peta Leaflet + OSM; pilih gedung dari daftar (peta terbang ke gedung) lalu geser pin; koordinat wajib berada di area kampus; detail laporan menampilkan peta mini | Must |
| F4 | Pencarian & Penyaringan (Fino) | Sebagai pencari, saya ingin menyaring laporan agar cepat menemukan barang saya | Kata kunci, jenis, kategori, gedung, rentang waktu (7/30/90 hari); hasil berupa kartu dengan paginasi; filter yang sama dipakai di Peta Global | Must |
| F5 | Pengajuan Klaim Kepemilikan (Harsya) | Sebagai pemilik, saya ingin mengklaim barang temuan agar bisa mengambilnya kembali | Klaim hanya untuk laporan Ditemukan milik orang lain; isi jawaban ciri rahasia + bukti; status berubah dari Menunggu menjadi Disetujui atau Ditolak; pengklaim bisa membatalkan selagi Menunggu | Must |
| F6 | Komunikasi & Pengembalian (Zeifira) | Sebagai penemu dan pemilik, kami ingin berkoordinasi agar serah terima mudah | Chat per klaim terbuka setelah klaim disetujui; pesan dimuat via AJAX tiap 5 detik; penemu/admin menekan "Konfirmasi Diserahkan" sehingga laporan Selesai | Must |
| F7 | Pengelolaan Administrasi (Alfi) | Sebagai admin, saya ingin memverifikasi klaim dan merapikan laporan | Dashboard ringkasan; tabel laporan, klaim, dan pengguna memakai DataTables dari JSON; bandingkan jawaban klaim dengan ciri rahasia; setujui/tolak (alasan wajib); sembunyikan laporan; nonaktifkan pengguna; CRUD kategori | Must |
| F8 | Integrasi & Pengujian (Fino) | Sebagai tim, kami ingin semua modul tersambung dan teruji | Seeder data demo; tes fitur (PHPUnit) untuk login, laporan, pencocokan, klaim; skenario uji end-to-end lulus | Must |
| F9 | Visualisasi Laporan Berbasis Foto (Faiz) | Sebagai pencari, saya ingin melihat barang sebagai foto di peta agar langsung mengenali barang saya | Marker berupa foto barang (bingkai warna + ikon sesuai jenis); laporan berdekatan dikelompokkan dengan foto terbaru + jumlah; klik untuk membuka kartu pratinjau; data dimuat via AJAX JSON | Must |
| F10 | Berbagi via Tautan, QR, Poster (Faiz) | Sebagai penemu, saya ingin menyebarkan laporan ke media sosial dan papan pengumuman | Tautan pendek `/r/{kode}`; kode QR; poster siap cetak (A5, `@media print`); gambar story 1080×1920 dapat diunduh | Should |
| F11 | AI Auto-Match (usulan: Harsya) | Sebagai pemilik barang, saya ingin diberi tahu otomatis kalau ada temuan yang mirip | Setiap laporan baru dicocokkan dengan laporan jenis lawan; skor 0 sampai 100 + alasan; maksimal 5 kandidat; tombol "Bukan barang saya"; berjalan tanpa AI (skor aturan) dan lebih akurat bila API key AI diisi | Should |
| F12 | Notifikasi Dalam Aplikasi (usulan: Zeifira) | Sebagai pengguna, saya ingin tahu kabar terbaru tanpa membuka setiap halaman | Ikon lonceng + jumlah belum dibaca (AJAX); notifikasi untuk kandidat cocok, klaim masuk, keputusan klaim, pesan baru; tandai sudah dibaca | Should |

## 5. Alur Pengguna dan Status

### 5.1 Alur utama

**Penemu (laporan Ditemukan)**
1. Login, tekan Laporkan, lalu pilih "Saya menemukan".
2. Isi judul, kategori, deskripsi, warna/merek, waktu ditemukan, **foto (wajib)**, dan **ciri rahasia** (hanya admin yang tahu).
3. Pilih gedung terdekat, geser pin ke titik persis, lalu kirim.
4. Laporan langsung muncul di Peta Global. Sistem mencari laporan hilang yang mirip dan memberi tahu pemiliknya.

**Pemilik (laporan Hilang)**
1. Login, tekan Laporkan, pilih "Saya kehilangan", isi data (foto boleh kosong), lalu kirim.
2. Sistem langsung mencari temuan yang mirip. Bila ada kandidat dengan skor ≥ 60, pemilik mendapat notifikasi **"Diduga cocok"**.
3. Pemilik membuka kandidat, menekan Ajukan Klaim, lalu menjawab ciri rahasia dan menulis bukti.
4. Admin membandingkan jawaban dengan ciri rahasia, lalu menyetujui (chat terbuka) atau menolak dengan alasan.
5. Penemu dan pemilik menyepakati serah terima lewat chat. Setelah barang berpindah tangan, penemu atau admin menekan Konfirmasi Diserahkan, dan laporan temuan serta laporan hilang terkait berstatus Selesai.

### 5.2 Status laporan

| Dari | Ke | Pemicu | Efek di peta |
|---|---|---|---|
| (baru) | Aktif `open` | Laporan dikirim | Tampil |
| Aktif | Proses Klaim `in_claim` | Ada klaim masuk | Tampil dengan label "Proses klaim" |
| Proses Klaim | Aktif | Semua klaim ditolak/dibatalkan | Tampil |
| Proses Klaim | Selesai `resolved` | Serah terima dikonfirmasi | Hilang dari peta, tetap di riwayat |
| Aktif (laporan hilang) | Selesai | Pemilik menandai "Sudah ketemu" atau klaim terkait selesai | Hilang dari peta |
| Aktif / Proses Klaim | Ditutup `closed` | Pemilik/admin menutup | Hilang dari peta |

Status Selesai dan Ditutup bersifat akhir.

### 5.3 Status klaim

| Dari | Ke | Pemicu |
|---|---|---|
| (baru) | Menunggu `pending` | Pengguna mengirim klaim |
| Menunggu | Dibatalkan `cancelled` | Pengklaim membatalkan |
| Menunggu | Ditolak `rejected` | Admin menolak (alasan wajib) |
| Menunggu | Disetujui `approved` | Admin menyetujui; klaim lain yang menunggu pada laporan yang sama otomatis ditolak |
| Disetujui | Diserahkan `returned` | Penemu atau admin mengonfirmasi serah terima |

## 6. Daftar Layar

L1 sampai L3, L5, L6, L9, dan L14 adalah layar inti demo; desain awalnya ada di FigCraft.

| Layar | Pengguna | Komponen | State yang harus tampil | Validasi |
|---|---|---|---|---|
| L1 Login | Semua | Logo, email, sandi (tampilkan/sembunyikan), tombol Masuk, tautan Daftar | Default, error kredensial, terblokir sementara, akun nonaktif | Kedua kolom wajib |
| L2 Registrasi | Tamu | Nama, NIM/NIP, email kampus, sandi + konfirmasi | Error per kolom | Email domain kampus, NIM unik 6 sampai 20 digit, sandi ≥ 8 |
| L3 Peta Global (beranda) | Semua | Peta penuh, chip jenis (Semua/Hilang/Ditemukan), filter kategori + periode, cari, marker foto, cluster, kartu pratinjau, daftar "Di sekitar sini" (desktop), tombol Laporkan, tombol tampilkan gedung | Memuat, kosong ("Belum ada laporan di filter ini"), terisi | - |
| L4 Jelajah Laporan | Semua | Filter lengkap, grid kartu foto, paginasi | Kosong, terisi | - |
| L5 Detail Laporan | Semua | Foto besar, label jenis + status, judul, kategori, waktu, gedung, deskripsi, peta mini, tombol Ajukan Klaim / Saya Menemukannya / Bagikan, kandidat cocok (khusus pemilik) | Aktif, proses klaim, selesai, milik sendiri | - |
| L6 Form Laporan | User | Pilihan jenis, data barang, unggah foto + pratinjau, waktu, pilih gedung + peta pin, ciri rahasia (khusus Ditemukan) | Default, error per kolom, sukses (flash) | Lihat aturan B4 sampai B7 |
| L7 Laporan Saya | User | Tab Aktif/Riwayat, kartu laporan, jumlah kandidat cocok | Kosong, terisi | - |
| L8 Notifikasi & Kandidat | User | Daftar notifikasi, kartu kandidat (skor, alasan, foto), tombol Lihat / Bukan barang saya | Belum dibaca, sudah dibaca, kosong | - |
| L9 Form Klaim | User | Ringkasan barang, pertanyaan ciri rahasia, jawaban, bukti, tautkan laporan hilang saya (opsional) | Default, error, sukses | Jawaban ≥ 5 karakter, bukti ≥ 20 karakter |
| L10 Klaim Saya & Detail Klaim | User | Daftar klaim (dikirim & masuk), linimasa status, chat, Konfirmasi Diserahkan | Menunggu, disetujui (chat aktif), ditolak (alasan), selesai | Pesan 1 sampai 1000 karakter |
| L11 Bagikan | Semua | Salin tautan, modal QR, poster A5 siap cetak, unduh gambar story | - | - |
| L12 Admin Dashboard | Admin | Kartu ringkasan (laporan aktif, klaim menunggu, selesai bulan ini, pengguna), daftar klaim terbaru | - | - |
| L13 Admin Laporan | Admin | DataTables (JSON), filter jenis/status, aksi sembunyikan/tutup | - | - |
| L14 Admin Verifikasi Klaim | Admin | DataTables klaim; halaman tinjau: jawaban vs ciri rahasia berdampingan, foto, riwayat; Setujui/Tolak | Menunggu, diputuskan | Alasan tolak ≥ 10 karakter |
| L15 Admin Pengguna | Admin | DataTables pengguna, aktif/nonaktif | - | Admin tidak bisa menonaktifkan diri sendiri |
| L16 Admin Kategori | Admin | CRUD kategori (pola `form.blade.php` seperti Modul 12) | - | Nama 3 sampai 40 karakter, unik |

## 7. Spesifikasi Peta Global (GIS)

Situs whereis Telkom University ternyata dibangun dengan **Leaflet + tile OpenStreetMap standar (Mapnik) + plugin marker cluster**. FindIt Campus memakai pustaka yang sama, jadi tampilannya terasa familiar bagi civitas Tel-U.

| Aspek | Spesifikasi |
|---|---|
| Pustaka | Leaflet 1.9.4, Leaflet.markercluster 1.5.3 |
| Tile | `https://tile.openstreetmap.org/{z}/{x}/{y}.png` + atribusi © OpenStreetMap contributors (wajib menurut kebijakan tile OSM) |
| Pusat & zoom awal | `-6.9733, 107.6304`, zoom 17; batas area kampus radius 1,5 km |
| Titik gedung | 34 gedung kampus (nama + koordinat dari peta publik whereis) di tabel `campus_locations`; tampil sebagai label kecil abu-abu yang bisa dinyalakan/dimatikan; dipakai juga di dropdown form |
| Marker laporan | Foto 52×52 px, sudut 14 px, bingkai putih 3 px, cincin 2 px berwarna jenis (Hilang amber, Ditemukan hijau) + lencana ikon di pojok (kaca pembesar = dicari, centang = ditemukan). Jadi jenis laporan tidak dibedakan dengan warna saja |
| Laporan tanpa foto | Petak berwarna lembut + ikon kategori |
| Cluster | Petak 60×60 px berisi foto laporan terbaru di kelompok itu + lencana jumlah; klik untuk zoom ke kelompok itu |
| Interaksi | Klik marker untuk membuka kartu pratinjau (foto, label jenis, judul, "2 jam lalu · Gedung KU3", tombol Lihat detail); tombol Lokasi saya; tombol Tampilkan gedung |
| Data | `GET /ajax/map/reports?type=&category=&days=&q=` mengembalikan JSON; marker dirender di browser (client-side rendering) |
| Kinerja | Hanya kolom yang diperlukan peta; thumbnail 320 px; maks 500 marker per permintaan |

## 8. Spesifikasi AI Auto-Match

### 8.1 Kapan berjalan
Setiap kali laporan dibuat (atau judul/deskripsi/kategori/lokasi/waktunya diubah), sistem mencari kandidat dari laporan **jenis lawan** yang berstatus Aktif.

### 8.2 Tahap 1: Skor aturan (selalu berjalan, tanpa biaya)
Syarat kandidat:
- kategori sama;
- waktu temuan ≥ waktu hilang − 1 hari dan selisihnya ≤ 30 hari;
- jarak antar pin ≤ 1.000 m (rumus haversine).

| Komponen | Bobot | Aturan |
|---|---|---|
| Jarak | 40 | ≤ 100 m: 40 · ≤ 300 m: 30 · ≤ 600 m: 20 · ≤ 1.000 m: 10 |
| Waktu | 25 | ≤ 1 hari: 25 · ≤ 3 hari: 18 · ≤ 7 hari: 10 · ≤ 30 hari: 4 |
| Kemiripan teks | 35 | Kata kunci judul + deskripsi + warna + merek (tanpa kata umum seperti "yang", "di", "warna"): rasio kata sama × 25, ditambah warna sama +5 dan merek sama +5 |

Kandidat dengan skor ≥ 40 disimpan (maksimal 5 teratas) di tabel `report_matches`.

### 8.3 Tahap 2: Penilaian AI (opsional)
Bila `ANTHROPIC_API_KEY` diisi di `.env`, sebuah *job* antrian mengirim laporan hilang dan maksimal 5 kandidat temuan ke Claude (model `claude-opus-5-5`, lewat SDK resmi `anthropic-ai/sdk`). Yang dikirim: judul, deskripsi, warna, merek, kategori, nama gedung, waktu, dan **foto** (thumbnail). AI bisa membandingkan deskripsi teks pemilik dengan foto temuan, sesuatu yang tidak bisa dilakukan skor aturan.
- Keluaran per kandidat: `score` 0 sampai 100 + `reason` ≤ 140 karakter dalam bahasa Indonesia, misalnya *"Warna biru dongker dan gantungan kunci boneka sesuai deskripsi; ditemukan 120 m dari lokasi hilang."*
- Skor akhir memakai skor AI bila ada, dan skor aturan bila tidak.
- Ciri rahasia tidak pernah dikirim ke AI, supaya tidak bocor lewat teks alasan.
- Perkiraan biaya: sekitar US$0,05 per pencocokan (beberapa foto kecil + teks). Demo dengan puluhan laporan menghabiskan < US$5.
- Bila API gagal atau key kosong, sistem tetap berjalan dengan skor aturan. Antarmuka menampilkan sumber skornya ("Skor aturan" / "Dinilai AI").

### 8.4 Notifikasi
Kandidat dengan skor akhir ≥ 60 memicu notifikasi ke **pemilik laporan hilang** ("Ada barang temuan yang diduga milikmu"). Penemu bisa melihat kandidat di halaman laporannya. Kandidat yang ditandai "Bukan barang saya" tidak muncul lagi.

## 9. Aturan Bisnis

| Kode | Aturan |
|---|---|
| B1 | Registrasi wajib email kampus (`@student.telkomuniversity.ac.id` atau `@telkomuniversity.ac.id`; daftar domain dapat diubah di `.env`). NIM/NIP berupa angka 6 sampai 20 digit dan unik. |
| B2 | Sandi minimal 8 karakter, disimpan sebagai hash bcrypt (`Hash::make`). |
| B3 | Login dibatasi 5 percobaan per menit (middleware `throttle`). Akun nonaktif ditolak dengan pesan jelas. |
| B4 | Judul 5 sampai 80 karakter; deskripsi minimal 20 karakter; kategori wajib; waktu kejadian tidak boleh di masa depan dan paling lama 60 hari lalu. |
| B5 | Pin lokasi wajib dan harus dalam radius 1,5 km dari pusat kampus. |
| B6 | Foto wajib untuk laporan Ditemukan dan opsional untuk Hilang; jpg/png/webp maksimal 4 MB dan 6000 x 6000 piksel; disimpan diperkecil (sisi terpanjang 1.280 px) + thumbnail 320 px. |
| B7 | Ciri rahasia wajib untuk laporan Ditemukan (minimal 5 karakter), tidak pernah tampil publik, dan tidak dikirim ke AI. |
| B8 | Maksimal 10 laporan baru per pengguna per hari. |
| B9 | Klaim hanya untuk laporan Ditemukan berstatus Aktif/Proses Klaim; tidak bisa mengklaim laporan sendiri; satu klaim aktif per laporan per pengguna; maksimal 3 klaim Menunggu per pengguna. |
| B10 | Penolakan klaim wajib alasan minimal 10 karakter. Menyetujui satu klaim otomatis menolak klaim lain yang Menunggu pada laporan yang sama. |
| B11 | Chat hanya untuk penemu, pengklaim yang klaimnya disetujui, dan admin. |
| B12 | Laporan hanya bisa diubah saat Aktif; dihapus hanya bila belum pernah diklaim (selain itu: Tutup). |
| B13 | Peta publik hanya menampilkan laporan Aktif/Proses Klaim yang tidak disembunyikan admin; periode bawaan 30 hari. |
| B14 | Barang yang klaimnya sudah disetujui tidak menerima klaim baru sampai serah terima selesai. |
| B15 | Pengguna yang klaimnya pernah ditolak tidak bisa mengklaim barang yang sama lagi, supaya ciri rahasia tidak bisa ditebak berulang kali. |
| B16 | Pelapor tidak bisa menutup laporan selama ada klaim yang disetujui. Admin tetap bisa menutup; semua klaim yang masih berjalan otomatis dibatalkan dan pengklaimnya diberi notifikasi. |
| B17 | Akun yang dinonaktifkan admin langsung keluar pada request berikutnya. |
| B18 | Setelah barang diserahkan, chat serah terima hanya bisa dibaca. |
| B19 | Admin tidak boleh memutuskan klaim yang melibatkan akunnya sendiri (sebagai pengklaim atau penemu); keputusan diserahkan ke admin lain. |
| B20 | Jenis laporan (hilang atau temuan) tidak bisa diganti setelah laporan dibuat. Bila salah pilih, pelapor menutup laporan lalu membuat laporan baru. |
| B21 | Hanya pemilik laporan hilang yang bisa menandai kandidat sebagai "Bukan barang saya". Klaim yang dibatalkan mengembalikan kandidatnya ke daftar; klaim yang ditolak menyembunyikannya. |

## 10. Model Data

Relasi: satu `user` punya banyak `reports`, `claims`, `messages`, `alerts`; satu `report` punya banyak `claims`; satu `claim` punya banyak `messages`; `report_matches` menghubungkan laporan hilang dan laporan temuan.

| Tabel | Kolom utama |
|---|---|
| `users` | id, name, nim, email, phone (opsional), password, role (`user`/`admin`), is_active, timestamps |
| `categories` | id, name, slug, icon (kelas Bootstrap Icons), timestamps |
| `campus_locations` | id, name, short_name, lat, lng, timestamps |
| `reports` | id, user_id, category_id, campus_location_id, type (`lost`/`found`), title, description, color, brand, secret_detail, event_at, location_note, lat, lng, photo, photo_thumb, status (`open`/`in_claim`/`resolved`/`closed`), is_hidden, share_code, timestamps |
| `report_matches` | id, lost_report_id, found_report_id, rule_score, ai_score, ai_reason, score, status (`suggested`/`dismissed`/`claimed`), timestamps · unik (lost_report_id, found_report_id) |
| `claims` | id, report_id, user_id, lost_report_id, secret_answer, proof, status (`pending`/`approved`/`rejected`/`cancelled`/`returned`), admin_note, reviewed_by, reviewed_at, returned_at, timestamps |
| `messages` | id, claim_id, user_id, body, timestamps |
| `alerts` | id, user_id, type, title, body, url, read_at, timestamps |
| `jobs`, `sessions`, `cache` | Bawaan Laravel (antrian job AI, sesi login) |

## 11. Kebutuhan Non-Fungsional

| Aspek | Kebutuhan |
|---|---|
| Keamanan | Semua form memakai `@csrf`; validasi selalu di server; sandi bcrypt; rute admin dilindungi middleware `auth` + `admin`; berkas unggahan divalidasi tipe dan ukurannya, lalu diproses ulang dengan GD sehingga metadata EXIF/GPS terbuang |
| Privasi | Ciri rahasia hanya terlihat oleh penemu dan admin; nomor HP tidak ditampilkan; form mengingatkan pengguna agar tidak memotret data pribadi (nomor KTM/KTP) dengan jelas, dan menyebutkan bahwa foto dapat diproses AI |
| Responsif | Nyaman dipakai dari lebar 360 px sampai desktop; Peta Global dirancang untuk HP lebih dulu |
| Aksesibilitas | Kontras teks memenuhi WCAG AA; jenis laporan dibedakan dengan warna dan ikon atau label; semua input punya `<label>` |
| Kinerja | Halaman peta tampil < 2 detik untuk 200 laporan; marker memakai thumbnail kecil; tabel admin memakai DataTables |
| Keandalan | Bila fitur AI gagal, sistem tetap berjalan dengan skor aturan |

## 12. Gaya Visual dan Design Tokens

Arah desain: **bersih, berbasis foto, dan terasa "kampus"**. Peta dan foto barang adalah bintang utamanya, jadi bingkai antarmuka sengaja dibuat tenang. Merah dipilih karena identik dengan Telkom University, dan dipakai hemat hanya untuk aksi utama. Logo dan nama resmi kampus **tidak** digunakan.

| Token | Nilai | Pemakaian |
|---|---|---|
| Ink | `#1B1F24` | Teks utama, ikon, tombol sekunder |
| Ink 2 | `#4A5058` | Teks sekunder |
| Muted | `#8A9099` | Placeholder, keterangan waktu |
| Line | `#E6E2DC` | Garis dan border kartu |
| Paper | `#FAF8F5` | Latar halaman (putih hangat) |
| Surface | `#FFFFFF` | Kartu, panel, form |
| Brand (Merah Kampus) | `#C1272D` (hover `#A21F25`) | Tombol aksi utama: Laporkan, Kirim, Ajukan Klaim; logo |
| Hilang | `#D97706` · tint `#FDF1DE` · teks `#9A4F05` | Marker, label "Hilang" |
| Ditemukan | `#1A7F5A` · tint `#E3F3EC` · teks `#13603F` | Marker, label "Ditemukan" |
| Error | `#B42318` | Pesan error form |
| Tipografi | **Plus Jakarta Sans** (dirancang di Jakarta, sangat terbaca di layar kecil); judul 24/28 px tebal 700, isi 15 sampai 16 px, keterangan 13 px | Semua teks |
| Radius | 6 (chip) · 10 (input, tombol) · 14 (kartu, marker foto) · 20 (bottom sheet) | - |
| Bayangan | Hanya untuk elemen yang melayang di atas peta (marker, kartu pratinjau, tombol Laporkan): `0 4px 14px rgba(27,31,36,.18)`. Kartu biasa datar dengan border 1 px | - |
| Spasi | Kelipatan 4: 4, 8, 12, 16, 24, 32, 48 | - |
| Ikon | Bootstrap Icons, dipilih berdasarkan relevansi (dompet, kunci, tas, laptop, dsb.) | - |
| Larangan | Tanpa gradien ungu-biru, glassmorphism, emoji di UI, atau lencana "AI Powered". Sumber skor cukup ditulis "Dinilai AI" | - |

## 13. Pemetaan ke Kriteria Penilaian

| Kriteria (slide Course Profile & Modul 7/8/15) | Dipenuhi oleh |
|---|---|
| Backend wajib Laravel | Laravel 12/13, MVC, resource controller |
| Session (Authentication & Login) | F1: `Auth::attempt`, middleware `auth`, middleware `admin`, sesi Laravel |
| Using Database | MySQL, 8 tabel aplikasi, migration, seeder, relasi Eloquent `hasMany`/`belongsTo`, eager loading |
| Features Complexity | GIS foto (F9), auto-match + AI (F11), klaim + chat (F5 sampai F6), admin (F7), berbagi QR/poster (F10) |
| Bonus: Client-Side Rendering | Peta Global, notifikasi, chat, dan tabel admin dirender di browser dari JSON (jQuery AJAX) |
| Framework Bootstrap | Seluruh halaman memakai Bootstrap 5.3 |
| 3 halaman Form, Table, CRUD | Form laporan (CRUD), CRUD kategori admin, tabel admin |
| jQuery, AJAX, JSON | `$.ajax` untuk peta, notifikasi, chat, DataTables |
| jQuery Plugin | DataTables (+ plugin Leaflet) |
| Data JSON untuk tabel/datatable | Tabel admin mengambil JSON dari `/admin/ajax/...` |
| Dokumentasi | PRD, panduan teknis, roadmap, README, laporan Tubes |

## 14. Di Luar Cakupan v1

Aplikasi mobile native; notifikasi email/WhatsApp/push; login SSO kampus (iGracias); kampus selain Bandung; peta dalam gedung per lantai; chat real-time berbasis WebSocket (v1 memakai polling AJAX); imbalan/hadiah untuk penemu.

## 15. Risiko dan Mitigasi

| Risiko | Mitigasi |
|---|---|
| Klaim palsu | Ciri rahasia + verifikasi admin + riwayat klaim per pengguna |
| Spam laporan | Batas 10 laporan/hari, admin bisa menyembunyikan laporan dan menonaktifkan akun |
| Biaya/kegagalan API AI | AI opsional; skor aturan selalu tersedia; job antrian tidak menghambat pengguna |
| Batas pemakaian tile OSM | Lalu lintas kecil (skala kampus), atribusi wajib dicantumkan; bisa diganti penyedia tile lain lewat satu konstanta |
| Foto memuat data pribadi | Peringatan di form + moderasi admin |

## Lampiran A: Titik Gedung Kampus

34 titik gedung (nama + koordinat) ada di `data/campus_locations.csv`, diambil dari peta publik whereis.telkomuniversity.ac.id, dan dipakai seeder `CampusLocationSeeder`. Kelompok sebaiknya mengecek ulang koordinatnya di lapangan sebelum demo.

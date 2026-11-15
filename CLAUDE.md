# Panduan untuk AI coding agent — FindIt Campus

Proyek Tugas Besar Pemrograman Web (Telkom University). Kode harus tetap sesuai materi kuliah supaya bisa dijelaskan anggota kelompok.

- Stack: Laravel 13 (PHP 8.3), MySQL, Blade, Bootstrap 5.3, jQuery 3.7 + `$.ajax`, DataTables, Leaflet + OpenStreetMap. Jangan menambah Vite/Tailwind/React/Livewire.
- Controller memakai pola resource seperti Modul 12: validasi `$request->validate([...])` berformat string, `redirect()->route(...)->with('success', ...)`, dan satu `form.blade.php` untuk tambah/ubah (`$title`, `$route`, `$method`).
- Model memakai `protected $fillable` dan relasi `hasMany`/`belongsTo`; nilai default kolom juga ditulis di `$attributes`.
- Logika lintas controller ditaruh di `app/Services` (MatchService, ClaimFlow, PhotoService, AiMatcher).
- Endpoint JSON berawalan `/ajax/...` atau `/admin/ajax/...` dan mengembalikan `response()->json([...], 200)`.
- JavaScript: satu file per halaman di `public/js`, dibungkus `$(document).ready`, teks dari pengguna lewat `escapeHtml()`.
- Teks antarmuka bahasa Indonesia; nama kode bahasa Inggris. Komentar singkat bahasa Indonesia.
- Warna dan radius memakai variabel CSS di `public/css/app.css` (token PRD §12). Jenis laporan jangan dibedakan dengan warna saja.
- `secret_detail` tidak boleh tampil publik, masuk JSON, atau dikirim ke AI.
- Jalankan `php artisan test` setelah perubahan; tes memakai SQLite memori.
- Dokumen produk: `C:\Users\hbtw2\OneDrive\Documents\PROWEB\tubes-findit\docs`.

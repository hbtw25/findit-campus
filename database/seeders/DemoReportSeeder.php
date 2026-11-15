<?php

namespace Database\Seeders;

use App\Models\CampusLocation;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Report;
use App\Models\ReportMatch;
use App\Models\User;
use App\Services\ClaimFlow;
use App\Services\MatchService;
use App\Services\PhotoService;
use Illuminate\Database\Seeder;

// Data demo: laporan hilang & temuan yang sebagian saling cocok,
// supaya auto-match, notifikasi, klaim, dan chat langsung terlihat saat presentasi.
class DemoReportSeeder extends Seeder
{
    public function run(): void
    {
        $photos = app(PhotoService::class);
        $users = User::pluck('id', 'email');
        $cats = Category::pluck('id', 'slug');
        $places = CampusLocation::get()->keyBy('short_name');

        $u = fn (string $name) => $users["{$name}@student.telkomuniversity.ac.id"];

        // [pelapor, jenis, judul, kategori, gedung, geser (m utara, m timur), jam lalu, warna, merek, deskripsi, foto, ciri rahasia]
        $rows = [
            ['rani', 'found', 'Kunci motor Honda gantungan boneka', 'kunci', 'Learning Center', [20, -15], 20, 'Hitam', 'Honda',
                'Kunci motor Honda dengan gantungan boneka kecil warna pink, ditemukan di bangku taman depan Learning Center.', 'kunci-motor', 'Ada kunci loker kecil bernomor 17 di gantungannya'],
            ['dimas', 'lost', 'Kunci motor Honda gantungan boneka pink', 'kunci', 'KU3', [10, 25], 26, 'Hitam', 'Honda',
                'Kunci motor Honda Beat, ada gantungan boneka kelinci pink. Terakhir dipakai di parkiran dekat KU3 sebelum kelas siang.', null, null],
            ['bagas', 'found', 'Tumbler biru dengan sedotan', 'botol-tumbler', 'Gedung K', [-10, 15], 50, 'Biru', 'Corkcicle',
                'Tumbler biru dongker dengan sedotan, tertinggal di kursi auditorium Gedung K setelah seminar.', 'tumbler-biru', 'Ada stiker inisial S.N. di bagian bawah'],
            ['salsa', 'lost', 'Tumbler biru Corkcicle', 'botol-tumbler', 'Gedung K', [15, 5], 72, 'Biru', 'Corkcicle',
                'Tumbler biru tua merek Corkcicle dengan sedotan, ketinggalan waktu seminar di auditorium Gedung K.', null, null],
            ['nadia', 'found', 'Dompet kulit coklat', 'dompet', 'KU3', [-12, -8], 2, 'Coklat', null,
                'Dompet kulit coklat ditemukan di meja kantin dekat KU3. Sudah dititipkan ke satpam pos depan.', 'dompet-coklat', 'Berisi KTM atas nama Bagas dan uang tiga lembar'],
            ['bagas', 'lost', 'Dompet kulit coklat isi KTM', 'dompet', 'KU3', [5, 10], 4, 'Coklat', null,
                'Dompet lipat kulit warna coklat, isinya KTM, SIM, dan sedikit uang. Kemungkinan jatuh di area kantin KU3.', null, null],
            ['dimas', 'found', 'KTM ditemukan di Gedung P', 'kartu-dokumen', 'Gedung P', [8, 12], 30, 'Merah', null,
                'Kartu tanda mahasiswa ditemukan di lantai 2 Gedung P dekat tangga. Silakan klaim dengan menyebutkan NIM.', 'ktm-merah', 'NIM berakhiran 4021'],
            ['nadia', 'lost', 'Headphone Sony hitam', 'elektronik', 'TULT', [10, 10], 120, 'Hitam', 'Sony',
                'Headphone Sony WH-CH520 hitam, tertinggal di lounge lantai 1 TULT. Ada goresan kecil di sisi kanan.', 'headphone-hitam', null],
            ['salsa', 'found', 'Headphone hitam di lounge TULT', 'elektronik', 'TULT', [-15, 20], 100, 'Hitam', 'Sony',
                'Headphone over-ear hitam ditemukan di sofa lounge TULT lantai 1.', 'headphone-hitam', 'Ada goresan di earcup kanan dan nama NK di bantalan'],
            ['rani', 'found', 'Payung lipat hijau', 'aksesoris', 'Student Center', [10, 5], 24, 'Hijau', null,
                'Payung lipat warna hijau tertinggal di depan sekretariat UKM Student Center.', 'payung-hijau', 'Gagang payung retak sedikit'],
            ['bagas', 'found', 'Laptop abu-abu di Learning Center', 'elektronik', 'Learning Center', [-20, 10], 6, 'Abu-abu', 'ASUS',
                'Laptop ASUS abu-abu tertinggal di meja diskusi lantai 3 Learning Center. Sudah diamankan petugas.', 'laptop-abu', 'Ada stiker Telkom di bawah touchpad'],
            ['rani', 'lost', 'HP Samsung hitam casing bening', 'elektronik', 'Masjid Syamsul Ulum', [0, 0], 46, 'Hitam', 'Samsung',
                'HP Samsung A54 hitam, casing bening dengan foto polaroid di dalamnya. Terakhir di serambi masjid setelah salat Zuhur.', 'hp-hitam', null],
            ['nadia', 'found', 'Jam tangan di Sport Center', 'aksesoris', 'Sport Center', [5, 15], 96, 'Hitam', null,
                'Jam tangan digital hitam ditemukan di ruang ganti Sport Center.', 'jam-tangan', 'Tali jam ada inisial A'],
            ['dimas', 'lost', 'Buku catatan coklat', 'buku-alat-tulis', 'FIT', [0, 0], 140, 'Coklat', null,
                'Buku catatan sampul coklat berisi catatan kuliah Pemrograman Web. Ada pembatas pita oranye.', 'buku-catatan', null],
            ['bagas', 'found', 'Earbuds putih di TULT', 'elektronik', 'TULT', [25, -10], 12, 'Putih', null,
                'Earbuds putih lengkap dengan case, ditemukan di meja lantai 5 TULT.', 'earbuds-putih', 'Case ada stiker bintang kecil'],
            ['rani', 'found', 'Flashdisk merah', 'elektronik', 'Gedung K', [20, -20], 3, 'Merah', 'SanDisk',
                'Flashdisk merah 32 GB tertancap di komputer lab Gedung K.', 'flashdisk-merah', 'Ada folder TUBES_WEBPRO di dalamnya'],
            ['salsa', 'lost', 'Kalkulator Casio fx-991', 'buku-alat-tulis', 'KU2', [0, 0], 190, 'Abu-abu', 'Casio',
                'Kalkulator ilmiah Casio fx-991 ID Plus, tertinggal setelah UTS di KU2.', 'kalkulator', null],
            ['dimas', 'found', 'Tote bag kanvas coklat', 'tas', 'Situ Techno', [-10, 0], 50, 'Coklat', null,
                'Tote bag kanvas coklat tertinggal di bangku pinggir Situ Techno, isinya botol minum dan buku.', 'tote-bag', 'Ada gantungan kunci bentuk bintang'],
            ['nadia', 'lost', 'Ransel abu-abu Eiger', 'tas', 'Gedung C', [0, 0], 230, 'Abu-abu', 'Eiger',
                'Ransel Eiger abu-abu dengan resleting kuning, berisi charger laptop dan jas hujan.', 'ransel-abu', null],
            ['salsa', 'found', 'Kacamata frame hitam', 'aksesoris', 'KU1', [0, 10], 210, 'Hitam', null,
                'Kacamata minus frame hitam di meja kelas KU1.', 'kacamata', 'Lensa kiri ada goresan kecil'],
        ];

        $created = [];
        foreach ($rows as $i => [$who, $type, $title, $cat, $place, $shift, $hoursAgo, $color, $brand, $desc, $photo, $secret]) {
            $loc = $places[$place];
            $data = [
                'user_id' => $u($who),
                'category_id' => $cats[$cat],
                'campus_location_id' => $loc->id,
                'type' => $type,
                'title' => $title,
                'description' => $desc,
                'color' => $color,
                'brand' => $brand,
                'secret_detail' => $secret,
                'event_at' => now()->subHours($hoursAgo)->startOfMinute(),
                // Geser beberapa meter dari titik gedung agar marker tidak menumpuk persis
                'lat' => $loc->lat + $shift[0] / 111320,
                'lng' => $loc->lng + $shift[1] / (111320 * cos(deg2rad($loc->lat))),
            ];

            if ($photo) {
                $data += $photos->store(database_path("seeders/images/{$photo}.png"));
            }

            $report = Report::create($data);
            $report->created_at = $report->event_at->copy()->addMinutes(20);
            $report->save();
            $created[$i] = $report;
        }

        // Jalankan auto-match untuk semua laporan hilang (membuat kandidat + notifikasi)
        $matcher = app(MatchService::class);
        foreach ($created as $report) {
            if ($report->isLost()) {
                $matcher->refreshFor($report);
            }
        }

        $flow = app(ClaimFlow::class);
        $admin = User::where('role', 'admin')->first();

        // Klaim menunggu verifikasi: Bagas mengklaim dompet temuan Nadia
        Claim::create([
            'report_id' => $created[4]->id,
            'user_id' => $u('bagas'),
            'lost_report_id' => $created[5]->id,
            'secret_answer' => 'Di dalamnya ada KTM atas nama saya, Bagas, dan uang sekitar tiga lembar lima puluh ribuan.',
            'proof' => 'Saya bisa tunjukkan foto dompet ini dari galeri HP dan SIM saya ada di dalamnya.',
        ]);
        $created[4]->update(['status' => 'in_claim']);

        // Klaim disetujui + chat: Nadia mengklaim headphone temuan Salsa
        $approved = Claim::create([
            'report_id' => $created[8]->id,
            'user_id' => $u('nadia'),
            'lost_report_id' => $created[7]->id,
            'secret_answer' => 'Earcup kanan ada goresan, dan di bantalan ada tulisan NK pakai spidol.',
            'proof' => 'Headphone dibeli bulan Agustus, saya punya foto kotak dan nota pembeliannya.',
        ]);
        $flow->approve($approved, $admin, 'Jawaban sesuai dengan ciri rahasia dari penemu.');
        ReportMatch::where('lost_report_id', $created[7]->id)
            ->where('found_report_id', $created[8]->id)
            ->update(['status' => 'claimed']);
        $approved->messages()->createMany([
            ['user_id' => $u('salsa'), 'body' => 'Halo Kak Nadia, headphone-nya aman sama aku. Bisa ketemu di lobby TULT?'],
            ['user_id' => $u('nadia'), 'body' => 'Bisa banget, makasih ya! Jam 13.00 setelah kelas gimana?'],
            ['user_id' => $u('salsa'), 'body' => 'Oke, aku tunggu di dekat meja resepsionis ya.'],
        ]);

        // Klaim selesai diserahkan: Bagas mengklaim kacamata temuan Salsa
        $returned = Claim::create([
            'report_id' => $created[19]->id,
            'user_id' => $u('bagas'),
            'secret_answer' => 'Lensa kiri ada goresan kecil di pojok bawah.',
            'proof' => 'Kacamata minus 2, frame hitam, saya pakai setiap kuliah di KU1.',
        ]);
        $created[19]->update(['status' => 'in_claim']);
        $flow->approve($returned, $admin);
        $flow->markReturned($returned->fresh());
    }
}

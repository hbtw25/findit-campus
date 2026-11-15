<?php

namespace Database\Seeders;

use App\Models\CampusLocation;
use Illuminate\Database\Seeder;

// 34 titik gedung Telkom University Bandung (nama + koordinat dari peta publik whereis.telkomuniversity.ac.id)
class CampusLocationSeeder extends Seeder
{
    public function run(): void
    {
        $file = fopen(database_path('data/campus_locations.csv'), 'r');
        fgetcsv($file); // lewati baris judul kolom

        while (($row = fgetcsv($file)) !== false) {
            [$name, $short, $lat, $lng] = $row;
            CampusLocation::updateOrCreate(['name' => $name], [
                'short_name' => $short,
                'lat' => $lat,
                'lng' => $lng,
            ]);
        }

        fclose($file);
    }
}

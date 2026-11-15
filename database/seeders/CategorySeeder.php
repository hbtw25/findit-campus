<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Nama kategori => ikon Bootstrap Icons
        $categories = [
            'Dompet' => 'wallet2',
            'Kartu & Dokumen' => 'person-vcard',
            'Kunci' => 'key',
            'Elektronik' => 'phone',
            'Tas' => 'backpack2',
            'Botol & Tumbler' => 'cup-straw',
            'Buku & Alat Tulis' => 'journal-bookmark',
            'Aksesoris' => 'eyeglasses',
            'Lainnya' => 'box-seam',
        ];

        foreach ($categories as $name => $icon) {
            Category::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'icon' => $icon]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Akun demo. Semua memakai kata sandi: findit123
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin FindIt', 'nim' => '199001012020', 'email' => 'admin@telkomuniversity.ac.id', 'role' => 'admin'],
            ['name' => 'Rani Putri Lestari', 'nim' => '103032400101', 'email' => 'rani@student.telkomuniversity.ac.id', 'role' => 'user'],
            ['name' => 'Dimas Ardiansyah', 'nim' => '103032400102', 'email' => 'dimas@student.telkomuniversity.ac.id', 'role' => 'user'],
            ['name' => 'Salsa Nabila', 'nim' => '103032400103', 'email' => 'salsa@student.telkomuniversity.ac.id', 'role' => 'user'],
            ['name' => 'Bagas Pratama', 'nim' => '103032400104', 'email' => 'bagas@student.telkomuniversity.ac.id', 'role' => 'user'],
            ['name' => 'Nadia Kusuma', 'nim' => '103032400105', 'email' => 'nadia@student.telkomuniversity.ac.id', 'role' => 'user'],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(['email' => $data['email']], $data + [
                'password' => Hash::make('findit123'),
                'is_active' => true,
            ]);
        }
    }
}

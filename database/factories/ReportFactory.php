<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::firstOrCreate(['slug' => 'kunci'], ['name' => 'Kunci', 'icon' => 'key'])->id,
            'type' => 'lost',
            'title' => 'Kunci motor Honda',
            'description' => 'Kunci motor Honda dengan gantungan boneka pink, hilang di parkiran.',
            'color' => 'Hitam',
            'brand' => 'Honda',
            'event_at' => now()->subHours(5),
            'lat' => -6.97295,
            'lng' => 107.62951,
        ];
    }

    public function found(): static
    {
        return $this->state(fn () => [
            'type' => 'found',
            'secret_detail' => 'Ada kunci loker nomor 17',
            'event_at' => now()->subHours(2),
        ]);
    }
}

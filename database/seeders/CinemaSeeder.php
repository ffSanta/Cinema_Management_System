<?php

namespace Database\Seeders;

use App\Models\Cinema;
use Illuminate\Database\Seeder;

class CinemaSeeder extends Seeder
{
    public function run(): void
    {
        $cinemas = [
            ['name' => 'โรง 1 (IMAX)', 'total_seats' => 300],
            ['name' => 'โรง 2 (4DX)', 'total_seats' => 180],
            ['name' => 'โรง 3 (Standard)', 'total_seats' => 220],
            ['name' => 'โรง 4 (Deluxe)', 'total_seats' => 120],
        ];

        foreach ($cinemas as $cinema) {
            Cinema::create($cinema);
        }
    }
}

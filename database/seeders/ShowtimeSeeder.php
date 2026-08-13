<?php

namespace Database\Seeders;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ShowtimeSeeder extends Seeder
{
    public function run(): void
    {
        $cinema = Cinema::first();
        $movies = Movie::take(3)->get();

        if (! $cinema || $movies->isEmpty()) {
            return;
        }

        // ห่างกันรอบละ 4 ชั่วโมง เพื่อไม่ให้เวลาชนกัน (หนังยาวสุด ~3 ชม.)
        $base = Carbon::tomorrow()->setTime(12, 0);

        foreach ($movies as $i => $movie) {
            Showtime::create([
                'movie_id' => $movie->id,
                'cinema_id' => $cinema->id,
                'show_time' => $base->copy()->addHours($i * 4),
                'price' => 250,
            ]);
        }
    }
}

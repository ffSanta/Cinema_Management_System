<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;

/**
 * Movie APIs — รายการภาพยนตร์ + รายละเอียด + รอบฉายของแต่ละเรื่อง
 */
class MovieController extends Controller
{
    /**
     * GET /api/movies — รายการภาพยนตร์ทั้งหมด (ใหม่สุดก่อน)
     */
    public function index(): JsonResponse
    {
        $movies = Movie::latest()->get()->map(fn (Movie $m) => $this->formatMovie($m));

        return response()->json(['data' => $movies]);
    }

    /**
     * GET /api/movies/{movie} — รายละเอียดภาพยนตร์ + รอบฉายที่ยังไม่ผ่าน
     */
    public function show(Movie $movie): JsonResponse
    {
        $data = $this->formatMovie($movie);
        $data['showtimes'] = $this->upcomingShowtimes($movie);

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/movies/{movie}/showtimes — เฉพาะรอบฉายของหนังเรื่องนี้ (ยังไม่ผ่าน)
     */
    public function showtimes(Movie $movie): JsonResponse
    {
        return response()->json([
            'movie' => $this->formatMovie($movie),
            'data' => $this->upcomingShowtimes($movie),
        ]);
    }

    /**
     * รอบฉายที่ยังไม่ผ่านของหนังเรื่องนี้ (พร้อมโรง + จำนวนที่นั่งว่าง)
     */
    private function upcomingShowtimes(Movie $movie): array
    {
        return $movie->showtimes()
            ->with('cinema')
            ->whereHas('cinema')
            ->withCount(['bookings' => fn ($q) => $q->where('status', 'booked')])
            ->where('show_time', '>=', now())
            ->orderBy('show_time')
            ->get()
            ->map(function ($s) {
                $total = (int) ($s->cinema->total_seats ?? 0);

                return [
                    'id' => $s->id,
                    'show_time' => $s->show_time->format('Y-m-d H:i'),
                    'show_time_iso' => $s->show_time->toIso8601String(),
                    'price' => (float) $s->price,
                    'price_premium' => (float) $s->price_premium,
                    'price_vip' => (float) $s->price_vip,
                    'cinema' => [
                        'id' => $s->cinema->id,
                        'name' => $s->cinema->name,
                        'total_seats' => $total,
                    ],
                    'booked_seats' => $s->bookings_count,
                    'available_seats' => max(0, $total - $s->bookings_count),
                ];
            })
            ->all();
    }

    /**
     * รูปแบบข้อมูลภาพยนตร์ (รวม URL ตัวอย่างหนัง YouTube)
     */
    private function formatMovie(Movie $movie): array
    {
        return [
            'id' => $movie->id,
            'title' => $movie->title,
            'duration_mins' => $movie->duration_mins,
            'synopsis' => $movie->synopsis,
            'poster_url' => $movie->poster_url,
            'trailer_url' => $movie->trailer_url,
            'youtube_id' => $movie->youtube_id,
            'youtube_embed_url' => $movie->youtube_embed_url,
        ];
    }
}

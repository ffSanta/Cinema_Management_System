<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use App\Support\SeatMap;
use Illuminate\Http\JsonResponse;

/**
 * Showtime + Seat Map / Pricing APIs
 */
class ShowtimeController extends Controller
{
    public function __construct(private readonly SeatMap $seatMap) {}

    /**
     * GET /api/showtimes — รอบฉายที่ยังไม่ผ่านทั้งหมด (พร้อมหนัง/โรง/ที่นั่งว่าง)
     */
    public function index(): JsonResponse
    {
        $showtimes = Showtime::with(['movie', 'cinema'])
            ->withCount(['bookings' => fn ($q) => $q->where('status', 'booked')])
            ->whereHas('movie')
            ->whereHas('cinema')
            ->where('show_time', '>=', now())
            ->orderBy('show_time')
            ->get()
            ->map(fn (Showtime $s) => $this->formatShowtime($s));

        return response()->json(['data' => $showtimes]);
    }

    /**
     * GET /api/showtimes/{showtime} — รายละเอียดรอบฉาย
     */
    public function show(Showtime $showtime): JsonResponse
    {
        $showtime->load(['movie', 'cinema'])
            ->loadCount(['bookings' => fn ($q) => $q->where('status', 'booked')]);

        return response()->json(['data' => $this->formatShowtime($showtime)]);
    }

    /**
     * GET /api/showtimes/{showtime}/seats
     * ผังที่นั่ง + สถานะการจอง + ราคาตามแถว/โซน
     */
    public function seats(Showtime $showtime): JsonResponse
    {
        $showtime->load(['movie', 'cinema']);

        $total = (int) ($showtime->cinema->total_seats ?? 0);
        $rows = $this->seatMap->rows($total, $showtime);

        // ที่นั่งที่ถูกจองแล้ว (status booked + ยังไม่ soft delete) → เช็คเร็วด้วย set
        $bookedSeats = $showtime->bookings()
            ->where('status', 'booked')
            ->pluck('seat_number')
            ->flip(); // ['A1' => 0, ...] ไว้ isset เร็ว

        // แปะสถานะ booked ราย seat
        $rowsOut = array_map(function (array $row) use ($bookedSeats) {
            $seats = array_map(fn (string $seat) => [
                'seat' => $seat,
                'booked' => isset($bookedSeats[$seat]),
            ], $row['seats']);

            return [
                'label' => $row['label'],
                'zone' => $row['zone'],
                'color' => $row['color'],
                'price' => $row['price'],
                'pairs' => $row['pairs'],
                'zone_start' => $row['zone_start'],
                'seats' => $seats,
            ];
        }, $rows);

        return response()->json([
            'showtime' => [
                'id' => $showtime->id,
                'show_time' => $showtime->show_time->format('Y-m-d H:i'),
                'show_time_iso' => $showtime->show_time->toIso8601String(),
                'movie' => [
                    'id' => $showtime->movie?->id,
                    'title' => $showtime->movie?->title,
                ],
                'cinema' => [
                    'id' => $showtime->cinema?->id,
                    'name' => $showtime->cinema?->name,
                    'total_seats' => $total,
                ],
            ],
            'zones' => $this->seatMap->legend($rows),   // legend ราคาแต่ละโซน
            'aisle_position' => $this->seatMap->aislePosition(),
            'seats_per_row' => SeatMap::SEATS_PER_ROW,
            'booked_seats' => $bookedSeats->keys()->all(),
            'rows' => $rowsOut,
        ]);
    }

    /**
     * รูปแบบข้อมูลรอบฉาย (พร้อมหนัง/โรง/ที่นั่งว่าง)
     */
    private function formatShowtime(Showtime $s): array
    {
        $total = (int) ($s->cinema->total_seats ?? 0);
        $booked = (int) ($s->bookings_count ?? 0);

        return [
            'id' => $s->id,
            'show_time' => $s->show_time->format('Y-m-d H:i'),
            'show_time_iso' => $s->show_time->toIso8601String(),
            'price' => (float) $s->price,
            'price_premium' => (float) $s->price_premium,
            'price_vip' => (float) $s->price_vip,
            'movie' => [
                'id' => $s->movie?->id,
                'title' => $s->movie?->title,
                'duration_mins' => $s->movie?->duration_mins,
                'poster_url' => $s->movie?->poster_url,
            ],
            'cinema' => [
                'id' => $s->cinema?->id,
                'name' => $s->cinema?->name,
                'total_seats' => $total,
            ],
            'booked_seats' => $booked,
            'available_seats' => max(0, $total - $booked),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Showtime;
use App\Support\SeatMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Booking APIs — จองตั๋ว, ประวัติการจอง, ยกเลิก (Soft Delete), กู้คืน
 * ต้องล็อกอิน (auth:sanctum) ทุก endpoint
 */
class BookingController extends Controller
{
    public function __construct(private readonly SeatMap $seatMap) {}

    /**
     * GET /api/bookings — ประวัติการจองของผู้ใช้ (รวมที่ยกเลิกแล้ว)
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::withTrashed()
            ->with([
                'showtime' => fn ($q) => $q->withTrashed(),
                'showtime.movie' => fn ($q) => $q->withTrashed(),
                'showtime.cinema' => fn ($q) => $q->withTrashed(),
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Booking $b) => $this->formatBooking($b));

        return response()->json(['data' => $bookings]);
    }

    /**
     * POST /api/showtimes/{showtime}/bookings — จองที่นั่ง (หลายที่นั่งได้)
     * body: { "seats": ["A1","A2"] }
     */
    public function store(Request $request, Showtime $showtime): JsonResponse
    {
        $validated = $request->validate([
            'seats' => ['required', 'array', 'min:1'],
            'seats.*' => ['string'],
        ], [
            'seats.required' => 'กรุณาเลือกที่นั่งอย่างน้อย 1 ที่',
            'seats.min' => 'กรุณาเลือกที่นั่งอย่างน้อย 1 ที่',
        ]);

        // รอบฉายต้องยังไม่ผ่านไป
        if ($showtime->show_time < now()) {
            return response()->json(['message' => 'ไม่สามารถจองได้ เนื่องจากรอบฉายนี้ผ่านไปแล้ว'], 422);
        }

        $seats = array_values(array_unique($validated['seats']));

        // แผนที่ราคาต่อที่นั่งตามโซน (ใช้ตรวจความถูกต้อง + คำนวณยอดรวมฝั่ง server)
        $priceMap = $this->seatMap->priceMap((int) $showtime->cinema->total_seats, $showtime);

        // ตรวจว่าที่นั่งที่ส่งมาอยู่ในผังจริง (กันส่งค่ามั่ว)
        if (array_diff($seats, array_keys($priceMap))) {
            return response()->json(['message' => 'ที่นั่งที่เลือกไม่ถูกต้อง'], 422);
        }

        // transaction + ล็อคแถว กันสองคนจองที่นั่งเดียวกันพร้อมกัน
        return DB::transaction(function () use ($request, $showtime, $seats, $priceMap) {
            $taken = Booking::where('showtime_id', $showtime->id)
                ->where('status', 'booked')
                ->whereIn('seat_number', $seats)
                ->lockForUpdate()
                ->pluck('seat_number')
                ->all();

            if ($taken) {
                return response()->json([
                    'message' => 'ที่นั่ง ' . implode(', ', $taken) . ' ถูกจองไปแล้ว กรุณาเลือกใหม่',
                    'taken' => $taken,
                ], 409);
            }

            $created = [];
            foreach ($seats as $seat) {
                $created[] = Booking::create([
                    'user_id' => $request->user()->id,
                    'showtime_id' => $showtime->id,
                    'seat_number' => $seat,
                    'status' => 'booked',
                ]);
            }

            $total = array_sum(array_map(fn ($s) => $priceMap[$s], $seats));

            return response()->json([
                'message' => 'จองตั๋วสำเร็จ ' . count($seats) . ' ที่นั่ง',
                'seats' => $seats,
                'total_price' => $total,
                'booking_ids' => array_map(fn ($b) => $b->id, $created),
            ], 201);
        });
    }

    /**
     * DELETE /api/bookings/{booking} — ยกเลิกการจอง (Soft Delete)
     */
    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        // เฉพาะเจ้าของการจองเท่านั้น
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'ไม่สามารถยกเลิกการจองของผู้อื่นได้'], 403);
        }

        $booking->update(['status' => 'cancelled']);
        $booking->delete(); // soft delete → seat ว่างอัตโนมัติ

        return response()->json(['message' => 'ยกเลิกการจองเรียบร้อยแล้ว']);
    }

    /**
     * PATCH /api/bookings/{booking}/restore — กู้คืนการจองที่ยกเลิก
     * (คืนได้เฉพาะเมื่อที่นั่งยังว่าง และรอบยังไม่ฉาย)
     */
    public function restore(Request $request, int $booking): JsonResponse
    {
        $booking = Booking::withTrashed()->find($booking);

        if (! $booking) {
            return response()->json(['message' => 'ไม่พบการจองนี้'], 404);
        }

        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'ไม่สามารถกู้คืนการจองของผู้อื่นได้'], 403);
        }

        if (! $booking->trashed()) {
            return response()->json(['message' => 'การจองนี้ยังไม่ได้ถูกยกเลิก'], 422);
        }

        $showtime = Showtime::find($booking->showtime_id);
        if (! $showtime) {
            return response()->json(['message' => 'ไม่สามารถกู้คืนได้ เนื่องจากรอบฉายนี้ถูกลบไปแล้ว'], 422);
        }

        if ($showtime->show_time < now()) {
            return response()->json(['message' => 'ไม่สามารถกู้คืนได้ เนื่องจากรอบฉายนี้ผ่านไปแล้ว'], 422);
        }

        return DB::transaction(function () use ($booking, $showtime) {
            $taken = Booking::where('showtime_id', $showtime->id)
                ->where('status', 'booked')
                ->where('seat_number', $booking->seat_number)
                ->lockForUpdate()
                ->exists();

            if ($taken) {
                return response()->json([
                    'message' => 'ที่นั่ง ' . $booking->seat_number . ' ถูกจองไปแล้ว ไม่สามารถกู้คืนได้',
                ], 409);
            }

            $booking->restore();
            $booking->update(['status' => 'booked']);

            return response()->json(['message' => 'กู้คืนการจองเรียบร้อยแล้ว']);
        });
    }

    /**
     * รูปแบบข้อมูลการจอง (พร้อมข้อมูลรอบฉาย/หนัง/โรง แม้ถูกลบไปแล้ว)
     */
    private function formatBooking(Booking $b): array
    {
        return [
            'id' => $b->id,
            'seat_number' => $b->seat_number,
            'status' => $b->trashed() ? 'cancelled' : 'booked',
            'is_cancelled' => $b->trashed(),
            'booked_at' => $b->created_at?->toIso8601String(),
            'showtime' => $b->showtime ? [
                'id' => $b->showtime->id,
                'show_time' => $b->showtime->show_time->format('Y-m-d H:i'),
                'show_time_iso' => $b->showtime->show_time->toIso8601String(),
                'movie' => $b->showtime->movie?->title,
                'cinema' => $b->showtime->cinema?->name,
            ] : null,
        ];
    }
}

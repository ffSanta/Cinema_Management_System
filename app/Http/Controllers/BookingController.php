<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Showtime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /** จำนวนที่นั่งต่อแถวในผังที่นั่ง (มากขึ้น = ผังกว้างแนวนอนเหมือนโรงหนังจริง) */
    private const SEATS_PER_ROW = 20;

    /**
     * แสดงรายการรอบฉายที่เปิดให้จอง (เฉพาะรอบที่ยังไม่ฉาย) จัดกลุ่มตามภาพยนตร์
     */
    public function index()
    {
        $showtimes = Showtime::with(['movie', 'cinema'])
            ->withCount(['bookings' => fn ($q) => $q->where('status', 'booked')])
            ->whereHas('movie')
            ->whereHas('cinema')
            ->where('show_time', '>=', now())
            ->orderBy('show_time')
            ->get()
            ->groupBy('movie.title');

        return view('booking.index', compact('showtimes'));
    }

    /**
     * แสดงผังที่นั่งของรอบฉายที่เลือก
     */
    public function selectSeats(Showtime $showtime)
    {
        $showtime->load(['movie', 'cinema']);

        $seatRows = $this->buildSeatRows($showtime->cinema->total_seats);

        // ที่นั่งที่ถูกจองแล้ว (status booked + ยังไม่ถูกยกเลิก/soft delete)
        $bookedSeats = $showtime->bookings()
            ->where('status', 'booked')
            ->pluck('seat_number')
            ->all();

        return view('booking.seats', compact('showtime', 'seatRows', 'bookedSeats'));
    }

    /**
     * ยืนยันการจอง (ผ่าน AJAX) + กันจองซ้ำแบบ application-level (แบบ B)
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

        $seats = array_values(array_unique($validated['seats']));

        // ตรวจว่าที่นั่งที่ส่งมาอยู่ในผังจริง (กันส่งค่ามั่ว)
        $validSeats = collect($this->buildSeatRows($showtime->cinema->total_seats))
            ->flatten()->all();

        if (array_diff($seats, $validSeats)) {
            return response()->json(['message' => 'ที่นั่งที่เลือกไม่ถูกต้อง'], 422);
        }

        // ทำใน transaction + ล็อคแถว กันสองคนจองที่นั่งเดียวกันพร้อมกัน
        return DB::transaction(function () use ($request, $showtime, $seats) {
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
                ], 422);
            }

            foreach ($seats as $seat) {
                Booking::create([
                    'user_id' => $request->user()->id,
                    'showtime_id' => $showtime->id,
                    'seat_number' => $seat,
                    'status' => 'booked',
                ]);
            }

            return response()->json([
                'message' => 'จองตั๋วสำเร็จ ' . count($seats) . ' ที่นั่ง',
                'seats' => $seats,
            ], 201);
        });
    }

    /**
     * ประวัติการจองของผู้ใช้ที่ล็อกอิน (รวมที่ยกเลิกแล้ว — withTrashed)
     */
    public function myBookings(Request $request)
    {
        $bookings = Booking::withTrashed()
            ->with(['showtime.movie', 'showtime.cinema'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('booking.my', compact('bookings'));
    }

    /**
     * ยกเลิกการจอง (Soft Delete) — ที่นั่งกลับมาว่างทันทีสำหรับรอบฉายนั้น
     */
    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        // เฉพาะเจ้าของการจองเท่านั้น
        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'ไม่สามารถยกเลิกการจองของผู้อื่นได้');
        }

        $booking->update(['status' => 'cancelled']);
        $booking->delete(); // soft delete → seat ว่างอัตโนมัติ (query จองใหม่กรอง trashed ออก)

        return response()->json(['message' => 'ยกเลิกการจองเรียบร้อยแล้ว']);
    }

    /**
     * สร้างผังที่นั่งจากจำนวนที่นั่งทั้งหมด → ['A' => ['A1'..'A10'], 'B' => [...], ...]
     */
    private function buildSeatRows(int $total): array
    {
        $rows = [];

        for ($i = 0; $i < $total; $i++) {
            $rowLabel = $this->rowLabel(intdiv($i, self::SEATS_PER_ROW));
            $col = ($i % self::SEATS_PER_ROW) + 1;
            $rows[$rowLabel][] = $rowLabel . $col;
        }

        return $rows;
    }

    /**
     * แปลงเลขแถว (0-based) เป็นตัวอักษรแบบ Excel: 0→A, 25→Z, 26→AA
     */
    private function rowLabel(int $n): string
    {
        $label = '';
        $n++;

        while ($n > 0) {
            $n--;
            $label = chr(65 + ($n % 26)) . $label;
            $n = intdiv($n, 26);
        }

        return $label;
    }
}

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
     * โซนราคาที่นั่ง — กำหนดตามสัดส่วนตำแหน่งแถว (0 = หน้าจอ, ใกล้ 1 = หลังสุด)
     * ยิ่งอยู่หลังยิ่งแพง; ราคา = ราคาฐานของรอบฉาย × multiplier
     * เรียง threshold มาก→น้อย (เช็คโซนแพงสุดก่อน)
     */
    private const ZONES = [
        ['name' => 'VIP (หลัง)', 'threshold' => 0.75, 'multiplier' => 1.6, 'color' => '#6f42c1'],
        ['name' => 'พรีเมียม',   'threshold' => 0.40, 'multiplier' => 1.3, 'color' => '#0d6efd'],
        ['name' => 'ธรรมดา',     'threshold' => 0.00, 'multiplier' => 1.0, 'color' => '#198754'],
    ];

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

        $seatRows = $this->buildSeatMap($showtime->cinema->total_seats, (float) $showtime->price);

        // ที่นั่งที่ถูกจองแล้ว (status booked + ยังไม่ถูกยกเลิก/soft delete)
        $bookedSeats = $showtime->bookings()
            ->where('status', 'booked')
            ->pluck('seat_number')
            ->all();

        // สรุปโซนสำหรับ legend — โซนที่มีในผังนี้ พร้อมราคา
        $zones = collect($seatRows)
            ->unique('zone')
            ->map(fn ($r) => ['zone' => $r['zone'], 'color' => $r['color'], 'price' => $r['price']])
            ->sortByDesc('price')
            ->values();

        return view('booking.seats', compact('showtime', 'seatRows', 'bookedSeats', 'zones'));
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

        // แผนที่ราคาต่อที่นั่งตามโซน (ใช้ทั้งตรวจความถูกต้อง + คำนวณยอดรวมฝั่ง server)
        $priceMap = $this->seatPriceMap($showtime->cinema->total_seats, (float) $showtime->price);

        // ตรวจว่าที่นั่งที่ส่งมาอยู่ในผังจริง (กันส่งค่ามั่ว)
        if (array_diff($seats, array_keys($priceMap))) {
            return response()->json(['message' => 'ที่นั่งที่เลือกไม่ถูกต้อง'], 422);
        }

        // ทำใน transaction + ล็อคแถว กันสองคนจองที่นั่งเดียวกันพร้อมกัน
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

            $total = array_sum(array_map(fn ($s) => $priceMap[$s], $seats));

            return response()->json([
                'message' => 'จองตั๋วสำเร็จ ' . count($seats) . ' ที่นั่ง รวม ' . number_format($total, 2) . ' บาท',
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
    /**
     * สร้างผังที่นั่งพร้อมข้อมูลโซน/ราคาแต่ละแถว
     * คืน array ของแถว: ['label','zone','color','price','seats'=>['A1',...]]
     */
    private function buildSeatMap(int $total, float $basePrice): array
    {
        $totalRows = (int) ceil($total / self::SEATS_PER_ROW);
        $rows = [];

        for ($i = 0; $i < $total; $i++) {
            $rowIndex = intdiv($i, self::SEATS_PER_ROW);

            if (! isset($rows[$rowIndex])) {
                $zone = $this->zoneForRow($rowIndex, $totalRows);
                $rows[$rowIndex] = [
                    'label' => $this->rowLabel($rowIndex),
                    'zone' => $zone['name'],
                    'color' => $zone['color'],
                    'price' => round($basePrice * $zone['multiplier'], 2),
                    'seats' => [],
                ];
            }

            $col = ($i % self::SEATS_PER_ROW) + 1;
            $rows[$rowIndex]['seats'][] = $rows[$rowIndex]['label'] . $col;
        }

        return array_values($rows);
    }

    /**
     * แผนที่ราคาต่อที่นั่ง: ['A1' => 111.00, 'L5' => 178.00, ...]
     */
    private function seatPriceMap(int $total, float $basePrice): array
    {
        $map = [];

        foreach ($this->buildSeatMap($total, $basePrice) as $row) {
            foreach ($row['seats'] as $seat) {
                $map[$seat] = $row['price'];
            }
        }

        return $map;
    }

    /**
     * หาโซนราคาของแถวจากสัดส่วนตำแหน่ง (0 = หน้าจอ, ใกล้ 1 = หลังสุด)
     */
    private function zoneForRow(int $rowIndex, int $totalRows): array
    {
        $fraction = $totalRows > 0 ? $rowIndex / $totalRows : 0;

        foreach (self::ZONES as $zone) {
            if ($fraction >= $zone['threshold']) {
                return $zone;
            }
        }

        return self::ZONES[array_key_last(self::ZONES)];
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

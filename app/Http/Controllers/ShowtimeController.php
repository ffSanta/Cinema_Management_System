<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ShowtimeController extends Controller
{
    /**
     * แสดงหน้ารายการรอบฉาย + ส่งรายชื่อหนัง/โรงไปทำ dropdown
     */
    public function index()
    {
        $movies = Movie::orderBy('title')->get(['id', 'title']);
        $cinemas = Cinema::orderBy('name')->get(['id', 'name']);

<<<<<<< HEAD
        // min ของ input = พรุ่งนี้ 00:00 → เลือกวันนี้/ก่อนหน้าไม่ได้
        $minShowTime = now()->addDay()->startOfDay()->format('Y-m-d\TH:i');
=======
        // min ของ date picker = เวลาปัจจุบันตามเวลาไทย (Asia/Bangkok) → เลือกเวลาที่ผ่านไปแล้วไม่ได้
        // (app timezone เป็น UTC จึงต้องแปลงเป็นเวลาไทยก่อน ไม่งั้น min จะช้ากว่าจริง 7 ชม.)
        $minShowTime = now('Asia/Bangkok')->format('Y-m-d H:i');
>>>>>>> 4b341c5 (fix calendar)

        return view('showtimes.index', compact('movies', 'cinemas', 'minShowTime'));
    }

    /**
     * ส่งข้อมูลรอบฉายเป็น JSON ให้ DataTables ผ่าน AJAX
     */
    public function data(Request $request): JsonResponse
    {
        $showtimes = Showtime::with([
            'movie' => fn($q) => $q->withTrashed(),
            'cinema' => fn($q) => $q->withTrashed(),
        ])->orderBy('show_time')->get()->map(function (Showtime $s) {
            $start = $s->show_time;
            $end = $start->copy()->addMinutes($s->movie?->duration_mins ?? 0);

            return [
                'id' => $s->id,
                'movie' => $s->movie?->title ?? '(ภาพยนตร์ถูกลบ)',
                'cinema' => $s->cinema?->name ?? '(โรงถูกลบ)',
                // display = ข้อความที่แสดง, timestamp = ค่าที่ใช้เรียงลำดับให้ถูกต้อง
                'show_time' => [
                    'display' => $start->format('d/m/Y H:i') . ' - ' . $end->format('H:i'),
                    'timestamp' => $start->timestamp,
                ],
                'price' => number_format((float) $s->price, 2),
            ];
        });

        return response()->json(['data' => $showtimes]);
    }

    /**
     * บันทึกรอบฉายใหม่ (ผ่าน AJAX) + เช็คเวลาชน
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateShowtime($request);

        if ($conflict = $this->findConflict($data['cinema_id'], $data['movie_id'], $data['show_time'])) {
            return $this->conflictResponse($conflict);
        }

        Showtime::create($data);

        return response()->json(['message' => 'เพิ่มรอบฉายเรียบร้อยแล้ว'], 201);
    }

    /**
     * ส่งข้อมูลรอบฉาย 1 รายการ (ไว้เติมในฟอร์มแก้ไข)
     */
    public function show(Showtime $showtime): JsonResponse
    {
        return response()->json([
            'id' => $showtime->id,
            'movie_id' => $showtime->movie_id,
            'cinema_id' => $showtime->cinema_id,
            // รูปแบบสำหรับ input datetime-local
            'show_time' => $showtime->show_time->format('Y-m-d\TH:i'),
            'price' => $showtime->price,
        ]);
    }

    /**
     * อัปเดตรอบฉาย (ผ่าน AJAX) + เช็คเวลาชน (ยกเว้นตัวเอง)
     */
    public function update(Request $request, Showtime $showtime): JsonResponse
    {
        $data = $this->validateShowtime($request);

        if ($conflict = $this->findConflict($data['cinema_id'], $data['movie_id'], $data['show_time'], $showtime->id)) {
            return $this->conflictResponse($conflict);
        }

        $showtime->update($data);

        return response()->json(['message' => 'แก้ไขรอบฉายเรียบร้อยแล้ว']);
    }

    /**
     * ลบรอบฉายแบบ Soft Delete (ผ่าน AJAX)
     */
    public function destroy(Showtime $showtime): JsonResponse
    {
        $showtime->delete();

        return response()->json(['message' => 'ลบรอบฉายเรียบร้อยแล้ว']);
    }

    /**
     * validation ร่วม store/update
     */
    private function validateShowtime(Request $request): array
    {
        return $request->validate([
            'movie_id' => ['required', 'exists:movies,id'],
            'cinema_id' => ['required', 'exists:cinemas,id'],
            'show_time' => ['required', 'date'],
            'price' => ['required', 'numeric', 'min:0', 'max:999.99'],
        ], [
            'movie_id.required' => 'กรุณาเลือกภาพยนตร์',
            'movie_id.exists' => 'ไม่พบภาพยนตร์ที่เลือก',
            'cinema_id.required' => 'กรุณาเลือกโรงภาพยนตร์',
            'cinema_id.exists' => 'ไม่พบโรงภาพยนตร์ที่เลือก',
            'show_time.required' => 'กรุณาเลือกเวลาฉาย',
            'show_time.date' => 'รูปแบบเวลาไม่ถูกต้อง',
            'price.required' => 'กรุณากรอกราคา',
            'price.numeric' => 'ราคาต้องเป็นตัวเลข',
            'price.min' => 'ราคาต้องไม่ติดลบ',
        ]);
    }

    /**
     * Business Logic: หารอบฉายในโรงเดียวกันที่ช่วงเวลาชนกัน
     * ช่วงเวลา = [show_time, show_time + duration ของหนัง]
     * ชนกันเมื่อ newStart < existingEnd และ existingStart < newEnd
     */
    private function findConflict(int $cinemaId, int $movieId, string $showTime, ?int $ignoreId = null): ?Showtime
    {
        $movie = Movie::withTrashed()->find($movieId);
        if (! $movie) {
            return null;
        }

        $newStart = Carbon::parse($showTime);
        $newEnd = $newStart->copy()->addMinutes($movie->duration_mins);

        $existing = Showtime::where('cinema_id', $cinemaId)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->with(['movie' => fn($q) => $q->withTrashed()])
            ->get();

        foreach ($existing as $s) {
            if (! $s->movie) {
                continue;
            }

            $sStart = $s->show_time;
            $sEnd = $sStart->copy()->addMinutes($s->movie->duration_mins);

            if ($newStart->lt($sEnd) && $sStart->lt($newEnd)) {
                return $s;
            }
        }

        return null;
    }

    /**
     * ตอบกลับ 422 พร้อมข้อความบอกว่าชนกับรอบไหน (โชว์ใต้ช่อง show_time)
     */
    private function conflictResponse(Showtime $conflict): JsonResponse
    {
        $start = $conflict->show_time->format('d/m/Y H:i');
        $end = $conflict->show_time->copy()
            ->addMinutes($conflict->movie->duration_mins)->format('H:i');

        return response()->json([
            'message' => 'ช่วงเวลาชนกับรอบฉายอื่น',
            'errors' => [
                'show_time' => [
                    'เวลาชนกับรอบฉาย "' . $conflict->movie->title . '" (' . $start . ' - ' . $end . ') ในโรงเดียวกัน',
                ],
            ],
        ], 422);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MovieController extends Controller
{
    /**
     * แสดงหน้ารายการภาพยนตร์ (โครง DataTables)
     */
    public function index()
    {
        return view('movies.index');
    }

    /**
     * ส่งข้อมูลภาพยนตร์เป็น JSON ให้ DataTables ผ่าน AJAX
     */
    public function data(Request $request): JsonResponse
    {
        $movies = Movie::latest()->get()->map(function (Movie $movie) {
            return [
                'id' => $movie->id,
                'poster_url' => $movie->poster_url,
                'title' => $movie->title,
                'duration_mins' => $movie->duration_mins,
                'synopsis' => $movie->synopsis,
                'created_at' => $movie->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json(['data' => $movies]);
    }

    /**
     * บันทึกภาพยนตร์ใหม่ (ผ่าน AJAX)
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        // อัปโหลดโปสเตอร์ (ถ้ามี) เก็บใน storage/app/public/posters
        if ($request->hasFile('poster_image')) {
            $data['poster_image'] = $request->file('poster_image')->store('posters', 'public');
        }

        $movie = Movie::create($data);

        return response()->json([
            'message' => 'เพิ่มภาพยนตร์เรียบร้อยแล้ว',
            'movie' => $movie,
        ], 201);
    }

    /**
     * ส่งข้อมูลภาพยนตร์ 1 เรื่อง (ไว้เติมในฟอร์มแก้ไข)
     */
    public function show(Movie $movie): JsonResponse
    {
        return response()->json([
            'id' => $movie->id,
            'title' => $movie->title,
            'duration_mins' => $movie->duration_mins,
            'synopsis' => $movie->synopsis,
            'trailer_url' => $movie->trailer_url,
            'poster_url' => $movie->poster_url,
            'has_poster' => (bool) $movie->poster_image,
        ]);
    }

    /**
     * อัปเดตภาพยนตร์ (ผ่าน AJAX)
     */
    public function update(Request $request, Movie $movie): JsonResponse
    {
        $data = $this->validatedData($request);

        // ถ้าอัปโหลดรูปใหม่: ลบรูปเก่าทิ้งก่อน แล้วเก็บรูปใหม่
        if ($request->hasFile('poster_image')) {
            if ($movie->poster_image) {
                Storage::disk('public')->delete($movie->poster_image);
            }
            $data['poster_image'] = $request->file('poster_image')->store('posters', 'public');
        }

        $movie->update($data);

        return response()->json([
            'message' => 'แก้ไขภาพยนตร์เรียบร้อยแล้ว',
            'movie' => $movie,
        ]);
    }

    /**
     * ลบภาพยนตร์แบบ Soft Delete (ผ่าน AJAX) + ลบไฟล์โปสเตอร์ออกจาก storage
     */
    public function destroy(Movie $movie): JsonResponse
    {
        // ลบไม่ได้ถ้ามีรอบฉายของหนังนี้ที่ยังมีการจอง active (ต้องยกเลิกการจองก่อน)
        if ($movie->showtimes()->whereHas('bookings', fn($q) => $q->where('status', 'booked'))->exists()) {
            return response()->json([
                'message' => 'ลบไม่ได้ — ภาพยนตร์นี้มีรอบฉายที่ถูกจองอยู่',
            ], 422);
        }

        // ลบไฟล์โปสเตอร์ออกจาก storage/app/public (สะท้อนไปที่ public/storage ผ่าน symlink)
        if ($movie->poster_image) {
            Storage::disk('public')->delete($movie->poster_image);
            $movie->update(['poster_image' => null]); // เคลียร์ path กัน record ชี้ไฟล์ที่ไม่มีแล้ว
        }

        $movie->delete(); // soft delete record

        return response()->json([
            'message' => 'ลบภาพยนตร์เรียบร้อยแล้ว',
        ]);
    }

    /**
     * validate + คืนเฉพาะข้อมูล text (ไม่รวมไฟล์ จัดการไฟล์แยกใน store/update)
     */
    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'duration_mins' => ['required', 'integer', 'min:1', 'max:1000'],
            'synopsis' => ['required', 'string', 'max:100'],
            // ลิงก์ตัวอย่างหนัง YouTube — ไม่บังคับ แต่ถ้ากรอกต้องเป็นลิงก์ YouTube ที่ถูกต้อง
            'trailer_url' => ['nullable', 'string', 'max:255', 'regex:~^https?://(?:www\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)[A-Za-z0-9_-]{11}~i'],
            'poster_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'title.required' => 'กรุณากรอกชื่อเรื่อง',
            'title.max' => 'ชื่อเรื่องต้องไม่เกิน 255 ตัวอักษร',
            'duration_mins.required' => 'กรุณากรอกความยาว',
            'duration_mins.integer' => 'ความยาวต้องเป็นตัวเลข',
            'duration_mins.min' => 'ความยาวต้องมากกว่า 0 นาที',
            'synopsis.required' => 'กรุณากรอกเรื่องย่อ',
            'synopsis.max' => 'เรื่องย่อต้องไม่เกิน 60 ตัวอักษร',
            'trailer_url.regex' => 'กรุณากรอกลิงก์ YouTube ที่ถูกต้อง',
            'trailer_url.max' => 'ลิงก์ยาวเกินไป',
            'poster_image.image' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น',
            'poster_image.mimes' => 'รองรับเฉพาะไฟล์ jpg, png, webp',
            'poster_image.max' => 'ขนาดรูปต้องไม่เกิน 2 MB',
        ]);

        // คืนเฉพาะ field ข้อความ (poster_image เป็นไฟล์ จัดการแยก)
        return array_intersect_key($validated, array_flip(['title', 'duration_mins', 'synopsis', 'trailer_url']));
    }
}

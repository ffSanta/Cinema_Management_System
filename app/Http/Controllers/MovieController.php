<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $validated = $this->validateMovie($request);

        $movie = Movie::create($validated);

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
        return response()->json($movie);
    }

    /**
     * อัปเดตภาพยนตร์ (ผ่าน AJAX)
     */
    public function update(Request $request, Movie $movie): JsonResponse
    {
        $validated = $this->validateMovie($request);

        $movie->update($validated);

        return response()->json([
            'message' => 'แก้ไขภาพยนตร์เรียบร้อยแล้ว',
            'movie' => $movie,
        ]);
    }

    /**
     * ลบภาพยนตร์แบบ Soft Delete (ผ่าน AJAX)
     */
    public function destroy(Movie $movie): JsonResponse
    {
        $movie->delete();

        return response()->json([
            'message' => 'ลบภาพยนตร์เรียบร้อยแล้ว',
        ]);
    }

    /**
     * กฎ validation ใช้ร่วมกันทั้ง store และ update
     */
    private function validateMovie(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:20'],
            'duration_mins' => ['required', 'integer', 'min:1', 'max:1000'],
            'synopsis' => ['required', 'string', 'max:60'],
        ], [
            'title.required' => 'กรุณากรอกชื่อเรื่อง',
            'duration_mins.required' => 'กรุณากรอกความยาว',
            'duration_mins.integer' => 'ความยาวต้องเป็นตัวเลข',
            'duration_mins.min' => 'ความยาวต้องมากกว่า 0 นาที',
            'synopsis.required' => 'กรุณากรอกเรื่องย่อ',
        ]);
    }
}

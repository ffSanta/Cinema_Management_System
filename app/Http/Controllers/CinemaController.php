<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CinemaController extends Controller
{
    /**
     * แสดงหน้ารายการโรงภาพยนตร์ (โครง DataTables)
     */
    public function index()
    {
        return view('cinemas.index');
    }

    /**
     * ส่งข้อมูลโรงภาพยนตร์เป็น JSON ให้ DataTables ผ่าน AJAX
     */
    public function data(Request $request): JsonResponse
    {
        $cinemas = Cinema::latest()->get()->map(function (Cinema $cinema) {
            return [
                'id' => $cinema->id,
                'name' => $cinema->name,
                'total_seats' => $cinema->total_seats,
                'created_at' => $cinema->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json(['data' => $cinemas]);
    }

    /**
     * บันทึกโรงภาพยนตร์ใหม่ (ผ่าน AJAX)
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCinema($request);

        $cinema = Cinema::create($data);

        return response()->json([
            'message' => 'เพิ่มโรงภาพยนตร์เรียบร้อยแล้ว',
            'cinema' => $cinema,
        ], 201);
    }

    /**
     * ส่งข้อมูลโรงภาพยนตร์ 1 แห่ง (ไว้เติมในฟอร์มแก้ไข)
     */
    public function show(Cinema $cinema): JsonResponse
    {
        return response()->json($cinema);
    }

    /**
     * อัปเดตโรงภาพยนตร์ (ผ่าน AJAX)
     */
    public function update(Request $request, Cinema $cinema): JsonResponse
    {
        $data = $this->validateCinema($request);

        $cinema->update($data);

        return response()->json([
            'message' => 'แก้ไขโรงภาพยนตร์เรียบร้อยแล้ว',
            'cinema' => $cinema,
        ]);
    }

    /**
     * ลบโรงภาพยนตร์แบบ Soft Delete (ผ่าน AJAX)
     */
    public function destroy(Cinema $cinema): JsonResponse
    {
        // ลบไม่ได้ถ้ามีรอบฉายของโรงนี้ที่ยังมีการจอง active (ต้องยกเลิกการจองก่อน)
        if ($cinema->showtimes()->whereHas('bookings', fn ($q) => $q->where('status', 'booked'))->exists()) {
            return response()->json([
                'message' => 'ลบไม่ได้ — โรงภาพยนตร์นี้มีรอบฉายที่ถูกจองอยู่ ต้องยกเลิกการจองก่อน',
            ], 422);
        }

        $cinema->delete();

        return response()->json([
            'message' => 'ลบโรงภาพยนตร์เรียบร้อยแล้ว',
        ]);
    }

    /**
     * กฎ validation ใช้ร่วมกันทั้ง store และ update
     */
    private function validateCinema(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'total_seats' => ['required', 'integer', 'min:1', 'max:300'],
        ], [
            'name.required' => 'กรุณากรอกชื่อโรงภาพยนตร์',
            'name.max' => 'ชื่อโรงภาพยนตร์ต้องไม่เกิน 50 ตัวอักษร',
            'total_seats.required' => 'กรุณากรอกจำนวนที่นั่ง',
            'total_seats.integer' => 'จำนวนที่นั่งต้องเป็นตัวเลข',
            'total_seats.min' => 'จำนวนที่นั่งต้องมากกว่า 0',
            'total_seats.max' => 'จำนวนที่นั่งต้องไม่เกิน 300',
        ]);
    }
}

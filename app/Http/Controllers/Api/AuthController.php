<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Authentication APIs สำหรับ Mobile — ใช้ Sanctum token
 * ทุก response เป็น JSON พร้อม status code ชัดเจน
 */
class AuthController extends Controller
{
    /**
     * POST /api/register — สมัครสมาชิก + ออก token ให้ทันที
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'กรุณากรอกชื่อ',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.confirmed' => 'ยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = $validated['password']; // hash อัตโนมัติจาก cast 'hashed'
        $user->role = 'user'; // สมาชิกใหม่จาก mobile เป็น user เสมอ
        $user->save();

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'สมัครสมาชิกเรียบร้อยแล้ว',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ], 201);
    }

    /**
     * POST /api/login — เข้าสู่ระบบ + ออก token
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'กรุณากรอกอีเมล',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
            ], 401);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'เข้าสู่ระบบเรียบร้อยแล้ว',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * POST /api/logout — เพิกถอน token ปัจจุบัน (token เดียวที่ใช้ยิงมา)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'ออกจากระบบเรียบร้อยแล้ว']);
    }

    /**
     * GET /api/profile — ข้อมูลผู้ใช้ที่ล็อกอินอยู่
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    /**
     * PUT /api/profile — แก้ไขโปรไฟล์ (ชื่อ/อีเมล + เปลี่ยนรหัสผ่านถ้าส่งมา)
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'กรุณากรอกชื่อ',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.confirmed' => 'ยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (! empty($validated['password'])) {
            $user->password = $validated['password']; // hash อัตโนมัติ
        }
        $user->save();

        return response()->json([
            'message' => 'อัปเดตโปรไฟล์เรียบร้อยแล้ว',
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * รูปแบบข้อมูล user ที่ส่งกลับ (ไม่รวม password)
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }
}

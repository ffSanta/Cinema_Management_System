<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\ShowtimeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (สำหรับ Mobile Application) — ทุก response เป็น JSON
|--------------------------------------------------------------------------
*/

// ===== Public: สมัคร/เข้าสู่ระบบ =====
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ===== Public: ดูภาพยนตร์/รอบฉาย/ผังที่นั่ง (เปิดให้เลื่อนดูก่อนล็อกอินได้) =====
Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/{movie}', [MovieController::class, 'show']);
Route::get('/movies/{movie}/showtimes', [MovieController::class, 'showtimes']);
Route::get('/showtimes', [ShowtimeController::class, 'index']);
Route::get('/showtimes/{showtime}', [ShowtimeController::class, 'show']);
Route::get('/showtimes/{showtime}/seats', [ShowtimeController::class, 'seats']);

// ===== Protected: ต้องมี token (Sanctum) =====
Route::middleware('auth:sanctum')->group(function () {
    // Auth / Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/avatar', [AuthController::class, 'uploadAvatar']);

    // Booking
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/showtimes/{showtime}/bookings', [BookingController::class, 'store']);
    Route::delete('/bookings/{booking}', [BookingController::class, 'cancel']);
    Route::patch('/bookings/{booking}/restore', [BookingController::class, 'restore']);
});

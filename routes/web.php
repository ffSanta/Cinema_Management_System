<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CinemaController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShowtimeController;
use App\Models\Movie;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $movies = Movie::latest()->get();

    return view('dashboard', compact('movies'));
});

// ===== ส่วนจัดการ (เฉพาะ admin) =====
Route::middleware(['auth', 'admin'])->group(function () {
    // Movies Management — เพิ่ม/แก้ไข/ลบ (admin เท่านั้น; ส่วนดูรายการอยู่กลุ่ม auth ด้านล่าง)
    Route::post('/movies', [MovieController::class, 'store'])->name('movies.store');
    Route::put('/movies/{movie}', [MovieController::class, 'update'])->name('movies.update');
    Route::delete('/movies/{movie}', [MovieController::class, 'destroy'])->name('movies.destroy');

    // Cinemas Management
    Route::get('/cinemas/data', [CinemaController::class, 'data'])->name('cinemas.data');
    Route::get('/cinemas', [CinemaController::class, 'index'])->name('cinemas.index');
    Route::post('/cinemas', [CinemaController::class, 'store'])->name('cinemas.store');
    Route::get('/cinemas/{cinema}', [CinemaController::class, 'show'])->name('cinemas.show');
    Route::put('/cinemas/{cinema}', [CinemaController::class, 'update'])->name('cinemas.update');
    Route::delete('/cinemas/{cinema}', [CinemaController::class, 'destroy'])->name('cinemas.destroy');

    // Showtimes Management
    Route::get('/showtimes/data', [ShowtimeController::class, 'data'])->name('showtimes.data');
    Route::get('/showtimes', [ShowtimeController::class, 'index'])->name('showtimes.index');
    Route::post('/showtimes', [ShowtimeController::class, 'store'])->name('showtimes.store');
    Route::get('/showtimes/{showtime}', [ShowtimeController::class, 'show'])->name('showtimes.show');
    Route::put('/showtimes/{showtime}', [ShowtimeController::class, 'update'])->name('showtimes.update');
    Route::delete('/showtimes/{showtime}', [ShowtimeController::class, 'destroy'])->name('showtimes.destroy');
});

// ===== Authentication =====
// สำหรับผู้ที่ยังไม่ล็อกอิน
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// สำหรับผู้ที่ล็อกอินแล้ว
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // ดูรายการภาพยนตร์ (ทั้ง admin และ user) — เพิ่ม/แก้ไข/ลบ เฉพาะ admin (กลุ่มด้านบน)
    Route::get('/movies/data', [MovieController::class, 'data'])->name('movies.data');
    Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
    Route::get('/movies/{movie}', [MovieController::class, 'show'])->name('movies.show');
});

// ===== Booking (เลือกรอบฉาย + จองที่นั่ง) — เฉพาะสมาชิก user, admin เข้าไม่ได้ =====
Route::middleware(['auth', 'user'])->group(function () {
    Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
    Route::get('/my-bookings', [BookingController::class, 'myBookings'])->name('booking.my');
    Route::get('/booking/{showtime}/seats', [BookingController::class, 'selectSeats'])->name('booking.seats');
    Route::post('/booking/{showtime}', [BookingController::class, 'store'])->name('booking.store');
    Route::delete('/booking/{booking}/cancel', [BookingController::class, 'cancel'])->name('booking.cancel');
    Route::patch('/booking/{booking}/restore', [BookingController::class, 'restore'])->name('booking.restore');
});

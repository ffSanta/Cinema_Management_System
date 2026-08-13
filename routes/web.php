<?php

use App\Http\Controllers\CinemaController;
use App\Http\Controllers\MovieController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
});

// Movies Management
Route::get('/movies/data', [MovieController::class, 'data'])->name('movies.data');
Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
Route::post('/movies', [MovieController::class, 'store'])->name('movies.store');
Route::get('/movies/{movie}', [MovieController::class, 'show'])->name('movies.show');
Route::put('/movies/{movie}', [MovieController::class, 'update'])->name('movies.update');
Route::delete('/movies/{movie}', [MovieController::class, 'destroy'])->name('movies.destroy');

// Cinemas Management
Route::get('/cinemas/data', [CinemaController::class, 'data'])->name('cinemas.data');
Route::get('/cinemas', [CinemaController::class, 'index'])->name('cinemas.index');
Route::post('/cinemas', [CinemaController::class, 'store'])->name('cinemas.store');
Route::get('/cinemas/{cinema}', [CinemaController::class, 'show'])->name('cinemas.show');
Route::put('/cinemas/{cinema}', [CinemaController::class, 'update'])->name('cinemas.update');
Route::delete('/cinemas/{cinema}', [CinemaController::class, 'destroy'])->name('cinemas.destroy');

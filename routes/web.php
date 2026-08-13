<?php

use App\Http\Controllers\MovieController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
});

// Movies Management
Route::get('/movies/data', [MovieController::class, 'data'])->name('movies.data');
Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');

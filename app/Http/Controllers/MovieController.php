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
}

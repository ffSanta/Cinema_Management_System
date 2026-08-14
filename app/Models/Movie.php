<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Movie extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'duration_mins',
        'poster_image',
        'synopsis',
    ];

    /**
     * URL เต็มของรูปโปสเตอร์ (ไว้ใช้แสดงผล)
     * ถ้ายังไม่มีรูป คืน placeholder
     */
    public function getPosterUrlAttribute(): string
    {
        if ($this->poster_image && Storage::disk('public')->exists($this->poster_image)) {
            return Storage::url($this->poster_image);
        }

        return 'https://placehold.co/80x120?text=No+Image';
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    /**
     * ลบภาพยนตร์ → ลบรอบฉายของหนังนั้นตามไปด้วย (cascade soft delete)
     * ลบแต่ละรอบทีละตัวเพื่อให้ event ของ Showtime ทำงาน (ต่อไปยัง booking)
     */
    protected static function booted(): void
    {
        static::deleting(function (Movie $movie) {
            if ($movie->isForceDeleting()) {
                $movie->showtimes()->withTrashed()->get()->each->forceDelete();
            } else {
                $movie->showtimes()->get()->each->delete();
            }
        });
    }
}

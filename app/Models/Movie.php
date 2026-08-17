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
        'trailer_url',
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

        // placeholder ทรงโปสเตอร์ (2:3) พื้นเข้ม พร้อมชื่อหนัง
        return 'https://placehold.co/300x450/1a1a2e/e94560?text=' . rawurlencode($this->title ?? 'No Image');
    }

    /**
     * ดึงรหัสวิดีโอ YouTube (11 ตัว) จาก trailer_url — รองรับหลายรูปแบบลิงก์
     * (youtube.com/watch?v=, youtu.be/, /embed/, /shorts/) คืน null ถ้าไม่พบ
     */
    public function getYoutubeIdAttribute(): ?string
    {
        if (! $this->trailer_url) {
            return null;
        }

        $pattern = '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~i';

        return preg_match($pattern, $this->trailer_url, $m) ? $m[1] : null;
    }

    /**
     * URL สำหรับฝัง iframe (embed) — คืน null ถ้าไม่มีตัวอย่างหนัง
     */
    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        return $this->youtube_id ? 'https://www.youtube.com/embed/' . $this->youtube_id : null;
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

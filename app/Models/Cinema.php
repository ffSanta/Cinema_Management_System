<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cinema extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'total_seats',
    ];

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    /**
     * ลบโรงภาพยนตร์ → ลบรอบฉายของโรงนั้นตามไปด้วย (cascade soft delete)
     * ลบแต่ละรอบทีละตัวเพื่อให้ event ของ Showtime ทำงาน (ต่อไปยัง booking)
     */
    protected static function booted(): void
    {
        static::deleting(function (Cinema $cinema) {
            if ($cinema->isForceDeleting()) {
                $cinema->showtimes()->withTrashed()->get()->each->forceDelete();
            } else {
                $cinema->showtimes()->get()->each->delete();
            }
        });
    }
}

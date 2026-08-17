<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Showtime extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'movie_id',
        'cinema_id',
        'show_time',
        'price',          // โซนธรรมดา
        'price_premium',  // โซนพรีเมียม
        'price_vip',      // โซน VIP
    ];

    protected $casts = [
        'show_time' => 'datetime',
        'price' => 'decimal:2',
        'price_premium' => 'decimal:2',
        'price_vip' => 'decimal:2',
    ];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function cinema(): BelongsTo
    {
        return $this->belongsTo(Cinema::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * ลบรอบฉาย → ลบ booking ของรอบนั้นตามไปด้วย (cascade soft delete)
     * ทำงานทั้งตอนลบรอบโดยตรง และตอน cascade มาจากการลบหนัง/โรง
     */
    protected static function booted(): void
    {
        static::deleting(function (Showtime $showtime) {
            if ($showtime->isForceDeleting()) {
                $showtime->bookings()->withTrashed()->get()->each->forceDelete();
            } else {
                $showtime->bookings()->get()->each->delete();
            }
        });
    }
}

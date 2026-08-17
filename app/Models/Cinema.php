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
     * โซนที่โรงนี้จะมีจริง (ตามจำนวนที่นั่ง) — คืน key: regular / premium / vip
     * ตรรกะเดียวกับการแบ่งโซนในผังที่นั่ง (แถวละ 20, แบ่งตามสัดส่วนตำแหน่งแถว)
     * เช่น 50 ที่นั่ง = 3 แถว → มีแค่ regular, premium (ไม่มี vip)
     */
    public function availableZoneKeys(): array
    {
        $perRow = 20;
        $thresholds = ['vip' => 0.75, 'premium' => 0.40, 'regular' => 0.00];
        $rows = (int) ceil($this->total_seats / $perRow);

        $keys = [];
        for ($r = 0; $r < $rows; $r++) {
            $fraction = $rows > 0 ? $r / $rows : 0;
            foreach ($thresholds as $key => $threshold) {
                if ($fraction >= $threshold) {
                    $keys[$key] = true;
                    break;
                }
            }
        }

        return array_keys($keys);
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

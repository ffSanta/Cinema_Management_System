<?php

namespace App\Support;

use App\Models\Showtime;
use Illuminate\Support\Collection;

/**
 * ตรรกะผังที่นั่ง/โซนราคา — ใช้ร่วมกันทั้งฝั่งเว็บ (Blade) และ API (Flutter)
 * ย้ายมาจาก BookingController เดิมเพื่อไม่ให้ราคา/โซนคำนวณคนละที่แล้วเพี้ยน
 */
class SeatMap
{
    /** จำนวนที่นั่งต่อแถวในผังที่นั่ง */
    public const SEATS_PER_ROW = 20;

    /**
     * โซนราคาที่นั่ง — กำหนดตามสัดส่วนตำแหน่งแถว (0 = หน้าจอ, ใกล้ 1 = หลังสุด)
     * column = คอลัมน์ราคาของโซนนั้นในตาราง showtimes (admin ตั้งเองต่อรอบ)
     * pairs = true → จัดที่นั่งแบบจับคู่ (1,2 - 3,4 - ...) แทนทางเดินกลาง
     * เรียง threshold มาก→น้อย (เช็คโซนแพงสุดก่อน)
     */
    private const ZONES = [
        ['name' => 'VIP (หลัง)', 'threshold' => 0.75, 'column' => 'price_vip',     'color' => '#6f42c1', 'pairs' => true],
        ['name' => 'พรีเมียม',   'threshold' => 0.40, 'column' => 'price_premium', 'color' => '#0d6efd', 'pairs' => false],
        ['name' => 'ธรรมดา',     'threshold' => 0.00, 'column' => 'price',         'color' => '#198754', 'pairs' => false],
    ];

    /**
     * สร้างผังที่นั่งพร้อมข้อมูลโซน/ราคาแต่ละแถว
     * คืน array ของแถว: ['label','zone','color','price','pairs','zone_start','seats'=>['A1',...]]
     */
    public function rows(int $total, Showtime $showtime): array
    {
        $totalRows = (int) ceil($total / self::SEATS_PER_ROW);
        $rows = [];
        $prevZone = null;

        for ($i = 0; $i < $total; $i++) {
            $rowIndex = intdiv($i, self::SEATS_PER_ROW);

            if (! isset($rows[$rowIndex])) {
                $zone = $this->zoneForRow($rowIndex, $totalRows);
                $rows[$rowIndex] = [
                    'label' => $this->rowLabel($rowIndex),
                    'zone' => $zone['name'],
                    'color' => $zone['color'],
                    // ราคาโซนที่ admin ตั้งไว้ต่อรอบ (fallback เป็นราคาธรรมดาถ้าโซนนั้นยังไม่ตั้ง)
                    'price' => (int) round($showtime->{$zone['column']} ?? $showtime->price),
                    'pairs' => $zone['pairs'],
                    'zone_start' => ($prevZone !== null && $prevZone !== $zone['name']),
                    'seats' => [],
                ];
                $prevZone = $zone['name'];
            }

            $col = ($i % self::SEATS_PER_ROW) + 1;
            $rows[$rowIndex]['seats'][] = $rows[$rowIndex]['label'] . $col;
        }

        return array_values($rows);
    }

    /**
     * แผนที่ราคาต่อที่นั่ง: ['A1' => 111, 'L5' => 178, ...]
     */
    public function priceMap(int $total, Showtime $showtime): array
    {
        $map = [];

        foreach ($this->rows($total, $showtime) as $row) {
            foreach ($row['seats'] as $seat) {
                $map[$seat] = $row['price'];
            }
        }

        return $map;
    }

    /**
     * สรุปโซนสำหรับ legend — โซนที่มีในผังนี้ พร้อมราคา (เรียงแพง→ถูก)
     */
    public function legend(array $rows): Collection
    {
        return collect($rows)
            ->unique('zone')
            ->map(fn ($r) => ['zone' => $r['zone'], 'color' => $r['color'], 'price' => $r['price']])
            ->sortByDesc('price')
            ->values();
    }

    /**
     * ตำแหน่งที่นั่งที่จะเว้นทางเดินกลาง (โซนที่ไม่ใช่ VIP) = กึ่งกลางแถว
     */
    public function aislePosition(): int
    {
        return intdiv(self::SEATS_PER_ROW, 2) + 1;
    }

    /**
     * หาโซนราคาของแถวจากสัดส่วนตำแหน่ง (0 = หน้าจอ, ใกล้ 1 = หลังสุด)
     */
    private function zoneForRow(int $rowIndex, int $totalRows): array
    {
        $fraction = $totalRows > 0 ? $rowIndex / $totalRows : 0;

        foreach (self::ZONES as $zone) {
            if ($fraction >= $zone['threshold']) {
                return $zone;
            }
        }

        return self::ZONES[array_key_last(self::ZONES)];
    }

    /**
     * แปลงเลขแถว (0-based) เป็นตัวอักษรแบบ Excel: 0→A, 25→Z, 26→AA
     */
    private function rowLabel(int $n): string
    {
        $label = '';
        $n++;

        while ($n > 0) {
            $n--;
            $label = chr(65 + ($n % 26)) . $label;
            $n = intdiv($n, 26);
        }

        return $label;
    }
}

<?php

namespace Database\Seeders;

use App\Models\Movie;
use Illuminate\Database\Seeder;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $movies = [
            [
                'title' => 'Inception',
                'duration_mins' => 148,
                'poster_image' => null,
                'synopsis' => 'นักขโมยที่เชี่ยวชาญการล้วงความลับจากจิตใต้สำนึกระหว่างการฝัน ได้รับภารกิจปลูกฝังความคิดแทนการขโมย',
            ],
            [
                'title' => 'Interstellar',
                'duration_mins' => 169,
                'poster_image' => null,
                'synopsis' => 'ทีมนักสำรวจอวกาศเดินทางผ่านรูหนอนเพื่อค้นหาดาวเคราะห์ดวงใหม่ที่มนุษย์อาศัยอยู่ได้',
            ],
            [
                'title' => 'The Dark Knight',
                'duration_mins' => 152,
                'poster_image' => null,
                'synopsis' => 'แบทแมนต้องเผชิญหน้ากับโจ๊กเกอร์ อาชญากรที่สร้างความโกลาหลไปทั่วเมืองก็อตแธม',
            ],
            [
                'title' => 'Parasite',
                'duration_mins' => 132,
                'poster_image' => null,
                'synopsis' => 'ครอบครัวยากจนค่อยๆ แทรกซึมเข้าไปทำงานในบ้านของครอบครัวมหาเศรษฐี จนนำไปสู่เหตุการณ์ไม่คาดฝัน',
            ],
            [
                'title' => 'Spirited Away',
                'duration_mins' => 125,
                'poster_image' => null,
                'synopsis' => 'เด็กหญิงหลงเข้าไปในโลกวิญญาณ และต้องทำงานในโรงอาบน้ำเพื่อช่วยพ่อแม่ที่ถูกสาปให้กลายเป็นหมู',
            ],
            [
                'title' => 'Avengers: Endgame',
                'duration_mins' => 181,
                'poster_image' => null,
                'synopsis' => 'เหล่าฮีโร่ที่เหลือรอดรวมตัวกันอีกครั้งเพื่อย้อนความเสียหายที่ธานอสก่อไว้',
            ],
        ];

        foreach ($movies as $movie) {
            Movie::create($movie);
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // ลิงก์ตัวอย่างหนัง (YouTube) — admin กรอก, user ดูเป็น iframe บนหน้าแรก
            $table->string('trailer_url')->nullable()->after('synopsis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('trailer_url');
        });
    }
};

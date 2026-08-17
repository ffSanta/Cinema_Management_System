<?php

use App\Models\Cinema;
use App\Models\Movie;
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
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Movie::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Cinema::class)->constrained()->cascadeOnDelete();
            $table->dateTime('show_time', precision: 0);
            $table->decimal('price', total: 8, places: 2);              // ราคาโซนธรรมดา
            $table->decimal('price_premium', total: 8, places: 2)->nullable(); // ราคาโซนพรีเมียม
            $table->decimal('price_vip', total: 8, places: 2)->nullable();     // ราคาโซน VIP
            $table->timestamps();
            $table->softDeletes('deleted_at', precision: 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('showtimes');
    }
};

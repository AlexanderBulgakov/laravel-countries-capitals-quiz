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
        Schema::create('capital_weather', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capital_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('temperature_c', 4, 1);
            $table->decimal('wind_speed_kmh', 4, 1);
            $table->unsignedSmallInteger('weather_code');
            $table->boolean('is_day');
            $table->timestamp('observed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capital_weather');
    }
};

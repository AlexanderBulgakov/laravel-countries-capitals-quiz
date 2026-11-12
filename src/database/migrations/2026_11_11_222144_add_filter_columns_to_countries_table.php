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
        Schema::table('countries', function (Blueprint $table) {
            $table->string('subregion')->nullable();
            $table->decimal('area_km2', 12, 2)->nullable();
            $table->decimal('density', 10, 2)->nullable()->storedAs('population / NULLIF(area_km2, 0)');
            $table->boolean('sovereign')->nullable();
            $table->jsonb('memberships')->default('[]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn([
                'density',
                'subregion',
                'area_km2',
                'sovereign',
                'memberships',
            ]);
        });
    }
};

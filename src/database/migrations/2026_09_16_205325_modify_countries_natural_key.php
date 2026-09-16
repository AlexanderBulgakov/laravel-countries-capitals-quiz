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
            $table->uuid('restcountries_uuid')->unique();
            $table->dropUnique(['cca3']);
            $table->string('cca3', 3)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('restcountries_uuid');
            $table->string('cca3', 3)->nullable(false)->change();
            $table->unique('cca3');
        });
    }
};

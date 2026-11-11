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
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('question_number');
            $table->foreignId('target_country_id')->constrained('countries');
            $table->foreignId('chosen_country_id')->nullable()->constrained('countries');
            $table->jsonb('option_country_ids');
            $table->timestamps();
            $table->unique(['quiz_attempt_id', 'question_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};

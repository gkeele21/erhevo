<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfm_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_year_id')->constrained('cfm_study_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('week_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('title');
            $table->string('slug');
            $table->boolean('is_special_topic')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['study_year_id', 'week_number']);
            $table->index(['start_date', 'end_date']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfm_weeks');
    }
};

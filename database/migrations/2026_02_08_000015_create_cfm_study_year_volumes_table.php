<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfm_study_year_volumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_year_id')->constrained('cfm_study_years')->cascadeOnDelete();
            $table->foreignId('volume_id')->constrained('scripture_volumes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['study_year_id', 'volume_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfm_study_year_volumes');
    }
};

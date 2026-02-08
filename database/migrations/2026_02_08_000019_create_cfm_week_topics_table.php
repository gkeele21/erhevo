<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfm_week_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_week_id')->constrained('cfm_weeks')->cascadeOnDelete();
            $table->foreignId('special_topic_id')->constrained('cfm_special_topics')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cfm_week_id', 'special_topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfm_week_topics');
    }
};

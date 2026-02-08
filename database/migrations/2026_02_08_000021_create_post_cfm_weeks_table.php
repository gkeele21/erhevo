<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_cfm_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('cfm_week_id')->constrained('cfm_weeks')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['post_id', 'cfm_week_id']);
            $table->index('cfm_week_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_cfm_weeks');
    }
};

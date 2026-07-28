<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // scripture | talks
            // Selection criteria: volume/books for scripture plans; author or
            // calling filters for talk plans. Kept as JSON since the two plan
            // types share no fields.
            $table->json('config');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('frequency')->nullable(); // daily | weekdays | weekly
            $table->timestamps();
        });

        Schema::create('study_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('session_number');
            $table->unsignedInteger('sort_order');
            $table->foreignId('scripture_chapter_id')->nullable()->constrained('scripture_chapters')->cascadeOnDelete();
            $table->foreignId('talk_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('scheduled_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['study_plan_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_plan_items');
        Schema::dropIfExists('study_plans');
    }
};

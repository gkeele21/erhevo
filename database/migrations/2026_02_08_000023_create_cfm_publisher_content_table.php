<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfm_publisher_content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publisher_id')->constrained('cfm_publishers')->cascadeOnDelete();
            $table->foreignId('cfm_week_id')->constrained('cfm_weeks')->cascadeOnDelete();
            $table->string('title');
            $table->string('content_type');
            $table->string('external_url');
            $table->text('description')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index(['cfm_week_id', 'content_type']);
            $table->index(['publisher_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfm_publisher_content');
    }
};

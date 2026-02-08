<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the post_scripture_references table.
     *
     * Supports multi-chapter ranges:
     * - "1 Nephi 3:7" -> start_chapter=3, start_verse=7, end_chapter=NULL, end_verse=NULL
     * - "1 Nephi 3:7-12" -> start_chapter=3, start_verse=7, end_chapter=NULL, end_verse=12
     * - "1 Nephi 3-4" -> start_chapter=3, start_verse=NULL, end_chapter=4, end_verse=NULL
     * - "1 Nephi 3:25-4:5" -> start_chapter=3, start_verse=25, end_chapter=4, end_verse=5
     */
    public function up(): void
    {
        Schema::create('post_scripture_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('start_chapter_id')->constrained('scripture_chapters')->cascadeOnDelete();
            $table->unsignedSmallInteger('start_verse')->nullable();
            $table->foreignId('end_chapter_id')->nullable()->constrained('scripture_chapters')->cascadeOnDelete();
            $table->unsignedSmallInteger('end_verse')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['start_chapter_id', 'start_verse']);
            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_scripture_references');
    }
};

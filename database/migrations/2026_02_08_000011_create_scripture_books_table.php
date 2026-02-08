<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scripture_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volume_id')->constrained('scripture_volumes')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('abbreviation', 20);
            $table->unsignedSmallInteger('sort_order');
            $table->unsignedSmallInteger('chapter_count');
            $table->timestamps();

            $table->unique(['volume_id', 'slug']);
            $table->index(['volume_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scripture_books');
    }
};

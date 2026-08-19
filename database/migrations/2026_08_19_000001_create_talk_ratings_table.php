<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One 1–5 star rating per user per talk. The talk's average is cached
        // on the talks table so the library can filter and sort on it.
        Schema::create('talk_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talk_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->unique(['talk_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talk_ratings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Starred temples, surfaced first on the temple list. Separate from
        // temple_visits: a favorite is an intention, a visit is a record.
        Schema::create('temple_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('temple_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'temple_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temple_favorites');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per date a user read a talk — a talk can be re-read, so this
        // is deliberately not unique per (talk, user), only per date.
        Schema::create('talk_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talk_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('read_on');
            $table->timestamps();

            $table->unique(['talk_id', 'user_id', 'read_on']);
            $table->index(['user_id', 'read_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talk_reads');
    }
};

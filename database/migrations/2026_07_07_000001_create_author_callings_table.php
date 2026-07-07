<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_callings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('authors')->cascadeOnDelete();
            $table->foreignId('church_calling_id')->constrained('church_callings')->cascadeOnDelete();
            $table->date('start_date')->nullable();
            // Null = currently held. Multiple null-ended rows per author model
            // concurrent callings.
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['author_id', 'end_date']);
            $table->index('church_calling_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_callings');
    }
};

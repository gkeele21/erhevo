<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            // Structured name; display_name is a verbatim override for mononyms
            // and irregular names (e.g. "Rumi", "President Nelson").
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('suffix')->nullable();
            $table->string('display_name')->nullable();
            $table->string('slug')->unique();
            // The author's *current* calling (nullable). The calling held when a
            // specific piece of content was authored lives on that content.
            $table->foreignId('church_calling_id')->nullable()->constrained('church_callings')->nullOnDelete();
            // Set when this author is also an app user.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('last_name');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};

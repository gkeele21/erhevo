<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Pending draft revision of a published lesson: the full lesson
            // form payload (title, description, items, ...) awaiting publish.
            $table->json('draft_data')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('draft_data');
        });
    }
};

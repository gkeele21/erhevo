<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talks', function (Blueprint $table) {
            // Optional link to the Author entity for the speaker. speaker_name
            // stays as the source-of-truth text; this enables exact filtering.
            $table->foreignId('author_id')->nullable()->after('speaker_name')
                ->constrained('authors')->nullOnDelete();
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::table('talks', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
            $table->dropColumn('author_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // First-class author link. author_text/author_user_id are kept during
            // the transition (dual-read) and dropped in a later migration.
            $table->foreignId('author_id')->nullable()->after('author_user_id')
                ->constrained('authors')->nullOnDelete();
            // The calling the author held when this post/quote was given, mirroring
            // talks.church_calling_id. Primary axis for "content given as an X".
            $table->foreignId('church_calling_id')->nullable()->after('author_id')
                ->constrained('church_callings')->nullOnDelete();

            $table->index('author_id');
            $table->index('church_calling_id');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
            $table->dropForeign(['church_calling_id']);
            $table->dropColumn(['author_id', 'church_calling_id']);
        });
    }
};

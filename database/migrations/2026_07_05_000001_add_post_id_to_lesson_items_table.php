<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lesson_items', function (Blueprint $table) {
            // Optional link to a Post this block references (e.g. a Quote item
            // pointing at a Quote-type post). Lets us find where a post is used.
            $table->foreignId('post_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('posts')
                ->nullOnDelete();

            $table->index('post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_items', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
            $table->dropColumn('post_id');
        });
    }
};

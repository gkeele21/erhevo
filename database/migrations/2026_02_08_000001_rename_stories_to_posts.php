<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Renames all story-related tables to post-related tables:
     * - stories -> posts
     * - story_tag -> post_tag
     * - story_edit_tokens -> post_edit_tokens
     * - story_images -> post_images
     */
    public function up(): void
    {
        // First, drop foreign key constraints that reference the stories table
        Schema::table('story_tag', function (Blueprint $table) {
            $table->dropForeign(['story_id']);
        });

        Schema::table('story_edit_tokens', function (Blueprint $table) {
            $table->dropForeign(['story_id']);
        });

        Schema::table('story_images', function (Blueprint $table) {
            $table->dropForeign(['story_id']);
        });

        // Rename the main stories table
        Schema::rename('stories', 'posts');

        // Rename story_tag to post_tag and update column
        Schema::rename('story_tag', 'post_tag');
        Schema::table('post_tag', function (Blueprint $table) {
            $table->renameColumn('story_id', 'post_id');
        });
        Schema::table('post_tag', function (Blueprint $table) {
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });

        // Rename story_edit_tokens to post_edit_tokens and update column
        Schema::rename('story_edit_tokens', 'post_edit_tokens');
        Schema::table('post_edit_tokens', function (Blueprint $table) {
            $table->renameColumn('story_id', 'post_id');
        });
        Schema::table('post_edit_tokens', function (Blueprint $table) {
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });

        // Rename story_images to post_images and update column
        Schema::rename('story_images', 'post_images');
        Schema::table('post_images', function (Blueprint $table) {
            $table->renameColumn('story_id', 'post_id');
        });
        Schema::table('post_images', function (Blueprint $table) {
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys
        Schema::table('post_tag', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
        });

        Schema::table('post_edit_tokens', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
        });

        Schema::table('post_images', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
        });

        // Rename posts back to stories
        Schema::rename('posts', 'stories');

        // Rename post_tag back to story_tag
        Schema::rename('post_tag', 'story_tag');
        Schema::table('story_tag', function (Blueprint $table) {
            $table->renameColumn('post_id', 'story_id');
        });
        Schema::table('story_tag', function (Blueprint $table) {
            $table->foreign('story_id')->references('id')->on('stories')->cascadeOnDelete();
        });

        // Rename post_edit_tokens back to story_edit_tokens
        Schema::rename('post_edit_tokens', 'story_edit_tokens');
        Schema::table('story_edit_tokens', function (Blueprint $table) {
            $table->renameColumn('post_id', 'story_id');
        });
        Schema::table('story_edit_tokens', function (Blueprint $table) {
            $table->foreign('story_id')->references('id')->on('stories')->cascadeOnDelete();
        });

        // Rename post_images back to story_images
        Schema::rename('post_images', 'story_images');
        Schema::table('story_images', function (Blueprint $table) {
            $table->renameColumn('post_id', 'story_id');
        });
        Schema::table('story_images', function (Blueprint $table) {
            $table->foreign('story_id')->references('id')->on('stories')->cascadeOnDelete();
        });
    }
};

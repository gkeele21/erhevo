<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // A supporting document (a PDF handout, a study guide) that a post
            // links to. Kept separate from cover_image, which is rendered as an
            // <img> everywhere a post is shown and so can only ever be a
            // picture. attachment_path is the storage path — the one the delete
            // endpoint scopes to the owner — while attachment_url is what the
            // page actually renders.
            $table->string('attachment_url', 2048)->nullable()->after('cover_image');
            $table->string('attachment_path', 2048)->nullable()->after('attachment_url');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_name');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'attachment_url',
                'attachment_path',
                'attachment_name',
                'attachment_size',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // 'lesson' or 'talk' — same builder, same visibility model; talks
            // just have no Come Follow Me link. ("kind" rather than "type"
            // because lesson_items.type already means the block type.)
            $table->string('kind')->default('lesson')->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};

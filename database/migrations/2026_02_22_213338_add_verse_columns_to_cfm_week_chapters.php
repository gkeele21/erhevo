<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfm_week_chapters', function (Blueprint $table) {
            $table->unsignedSmallInteger('start_verse')->nullable()->after('chapter_id');
            $table->unsignedSmallInteger('end_verse')->nullable()->after('start_verse');
        });
    }

    public function down(): void
    {
        Schema::table('cfm_week_chapters', function (Blueprint $table) {
            $table->dropColumn(['start_verse', 'end_verse']);
        });
    }
};

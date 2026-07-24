<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Set the first time a lesson is published and never reset, so an
            // unpublish/republish cycle keeps the original publication date.
            // published_at then tracks the most recent publish.
            $table->timestamp('first_published_at')->nullable()->after('published_at');
        });

        DB::table('lessons')
            ->whereNotNull('published_at')
            ->update(['first_published_at' => DB::raw('published_at')]);
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('first_published_at');
        });
    }
};

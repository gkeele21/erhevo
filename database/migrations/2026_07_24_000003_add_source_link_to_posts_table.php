<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Where the content was found (e.g. a Facebook post): the link
            // itself plus the platform label derived from its host.
            $table->string('source_url', 2048)->nullable()->after('date_given');
            $table->string('source_platform', 50)->nullable()->after('source_url');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['source_url', 'source_platform']);
        });
    }
};

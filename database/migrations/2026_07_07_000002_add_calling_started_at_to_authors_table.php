<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            // Start date of the author's primary current calling
            // (church_calling_id). Full history lives in author_callings.
            $table->date('calling_started_at')->nullable()->after('church_calling_id');
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn('calling_started_at');
        });
    }
};

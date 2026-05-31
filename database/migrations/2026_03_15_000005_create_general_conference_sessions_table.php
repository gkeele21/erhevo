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
        Schema::create('general_conference_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('general_conference_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_type_id')->constrained('general_conference_session_types')->cascadeOnDelete();
            $table->string('name');
            $table->date('session_date');
            $table->smallInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_conference_sessions');
    }
};

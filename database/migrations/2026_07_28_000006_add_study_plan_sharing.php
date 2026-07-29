<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Friends the owner is studying with: members see the plan and share
        // one completion state (any member can check a reading off).
        Schema::create('study_plan_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['study_plan_id', 'user_id']);
        });

        Schema::table('study_plan_items', function (Blueprint $table) {
            $table->foreignId('completed_by')->nullable()->after('completed_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('study_plan_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completed_by');
        });
        Schema::dropIfExists('study_plan_members');
    }
};

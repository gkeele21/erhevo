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
        Schema::create('talks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('talk_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('general_conference_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('speaker_name');
            $table->string('speaker_title')->nullable();
            $table->foreignId('church_calling_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('church_organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('summary')->nullable();
            $table->date('talk_date')->nullable();
            $table->string('url')->nullable();
            $table->smallInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['source_id', 'slug']);
            $table->index(['talk_date']);
            $table->index(['speaker_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talks');
    }
};

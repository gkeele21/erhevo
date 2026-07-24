<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->longText('content_anonymized')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('cover_image')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('author_type')->default('self');
            $table->string('author_text')->nullable();
            // No FK on SQLite: this column is dropped in 2026_07_06_000003, and
            // SQLite can't DROP COLUMN a column named in a foreign key definition.
            if (DB::getDriverName() === 'sqlite') {
                $table->foreignId('author_user_id')->nullable();
            } else {
                $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visibility')->default('public');
            $table->boolean('hide_creator')->default(false);
            $table->boolean('hide_author')->default(false);
            $table->boolean('anonymize_names')->default(false);
            $table->json('name_mappings')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['visibility', 'published_at']);
            $table->index(['user_id', 'visibility']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};

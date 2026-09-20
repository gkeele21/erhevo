<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Generalise post_scripture_references into scripture_references.
 *
 * Posts were the only thing that could point at a passage, but lesson items do
 * too — and a passage page wants one query across both, not a union. The table
 * is rebuilt rather than altered in place so MySQL and SQLite behave the same
 * (dropping the post_id foreign key differs between them).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scripture_references', function (Blueprint $table) {
            $table->id();
            $table->morphs('referenceable');
            $table->foreignId('start_chapter_id')->constrained('scripture_chapters')->cascadeOnDelete();
            $table->unsignedSmallInteger('start_verse')->nullable();
            $table->foreignId('end_chapter_id')->nullable()->constrained('scripture_chapters')->cascadeOnDelete();
            $table->unsignedSmallInteger('end_verse')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['start_chapter_id', 'start_verse']);
        });

        if (Schema::hasTable('post_scripture_references')) {
            DB::table('post_scripture_references')->orderBy('id')->chunk(500, function ($rows) {
                DB::table('scripture_references')->insert(
                    collect($rows)->map(fn ($row) => [
                        'referenceable_type' => \App\Models\Post::class,
                        'referenceable_id' => $row->post_id,
                        'start_chapter_id' => $row->start_chapter_id,
                        'start_verse' => $row->start_verse,
                        'end_chapter_id' => $row->end_chapter_id,
                        'end_verse' => $row->end_verse,
                        'sort_order' => $row->sort_order,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ])->all()
                );
            });

            Schema::dropIfExists('post_scripture_references');
        }
    }

    public function down(): void
    {
        Schema::create('post_scripture_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('start_chapter_id')->constrained('scripture_chapters')->cascadeOnDelete();
            $table->unsignedSmallInteger('start_verse')->nullable();
            $table->foreignId('end_chapter_id')->nullable()->constrained('scripture_chapters')->cascadeOnDelete();
            $table->unsignedSmallInteger('end_verse')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['start_chapter_id', 'start_verse']);
            $table->index('post_id');
        });

        // Only post references have a home in the old shape; lesson-item ones
        // are dropped with the table.
        DB::table('scripture_references')
            ->where('referenceable_type', \App\Models\Post::class)
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                DB::table('post_scripture_references')->insert(
                    collect($rows)->map(fn ($row) => [
                        'post_id' => $row->referenceable_id,
                        'start_chapter_id' => $row->start_chapter_id,
                        'start_verse' => $row->start_verse,
                        'end_chapter_id' => $row->end_chapter_id,
                        'end_verse' => $row->end_verse,
                        'sort_order' => $row->sort_order,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ])->all()
                );
            });

        Schema::dropIfExists('scripture_references');
    }
};

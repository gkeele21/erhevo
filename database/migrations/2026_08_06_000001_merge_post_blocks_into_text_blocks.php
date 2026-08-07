<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The "My Post" block type merged into the "My Writing" (text) block: pulling
 * in an existing post is now a mode of the text block rather than a block
 * type of its own. Items keep their post_id linkage and config snapshot, so
 * display and "which lessons use this post" behave exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('lesson_items')->where('type', 'post')->update(['type' => 'text']);

        // Pending drafts embed items as JSON — convert those too.
        DB::table('lessons')->whereNotNull('draft_data')->orderBy('id')
            ->each(function ($lesson) {
                $draft = json_decode($lesson->draft_data, true);
                if (! is_array($draft) || empty($draft['items'])) {
                    return;
                }

                $draft['items'] = array_map(function ($item) {
                    if (($item['type'] ?? null) === 'post') {
                        $item['type'] = 'text';
                    }
                    foreach ($item['children'] ?? [] as $i => $child) {
                        if (($child['type'] ?? null) === 'post') {
                            $item['children'][$i]['type'] = 'text';
                        }
                    }

                    return $item;
                }, $draft['items']);

                DB::table('lessons')->where('id', $lesson->id)
                    ->update(['draft_data' => json_encode($draft)]);
            });
    }

    public function down(): void
    {
        // Not reversible: post-backed text blocks are indistinguishable from
        // text blocks whose writing was saved as a post.
    }
};

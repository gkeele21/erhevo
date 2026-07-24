<?php

use App\Models\Author;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Safety net: ensure every post's attribution is migrated into author_id
        // BEFORE the legacy columns are dropped. Uses the query builder (not the
        // Post model) so it doesn't depend on the now-reduced AuthorType enum.
        foreach (DB::table('posts')->where('author_type', 'text')->whereNotNull('author_text')->whereNull('author_id')->distinct()->pluck('author_text') as $name) {
            $author = Author::findOrCreateByName($name);
            DB::table('posts')->where('author_type', 'text')->where('author_text', $name)->whereNull('author_id')->update(['author_id' => $author->id]);
        }

        foreach (DB::table('posts')->where('author_type', 'user')->whereNotNull('author_user_id')->whereNull('author_id')->distinct()->pluck('author_user_id') as $userId) {
            if ($user = User::find($userId)) {
                DB::table('posts')->where('author_type', 'user')->where('author_user_id', $userId)->whereNull('author_id')->update(['author_id' => Author::forUser($user)->id]);
            }
        }

        foreach (DB::table('posts')->where('author_type', 'self')->whereNull('author_id')->distinct()->pluck('user_id') as $userId) {
            if ($user = User::find($userId)) {
                DB::table('posts')->where('author_type', 'self')->where('user_id', $userId)->whereNull('author_id')->update(['author_id' => Author::forUser($user)->id]);
            }
        }

        // Collapse author_type: text/user both become the entity-backed "author".
        DB::table('posts')->whereIn('author_type', ['text', 'user'])->update(['author_type' => 'author']);

        // The FK may retain its pre-rename name (stories_author_user_id_foreign),
        // so resolve it from the schema rather than assuming Laravel's convention.
        // MySQL only: SQLite (used in tests) rebuilds the table on dropColumn and
        // sheds the FK automatically, and information_schema doesn't exist there.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts'
                 AND COLUMN_NAME = 'author_user_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
            );
            foreach ($foreignKeys as $fk) {
                DB::statement("ALTER TABLE `posts` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['author_text', 'author_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('author_text')->nullable()->after('author_type');
            $table->foreignId('author_user_id')->nullable()->after('author_text')->constrained('users')->nullOnDelete();
        });

        // Best-effort: map the collapsed type back to the legacy "text".
        DB::table('posts')->where('author_type', 'author')->update(['author_type' => 'text']);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Denormalized rating aggregate, recalculated by App\Models\TalkRating.
        // Cached because the library filters and sorts on it across thousands
        // of talks, which a HAVING on an aggregate subquery does poorly.
        Schema::table('talks', function (Blueprint $table) {
            $table->decimal('average_rating', 3, 2)->nullable()->after('display_order');
            $table->unsignedInteger('ratings_count')->default(0)->after('average_rating');

            $table->index('average_rating');
        });
    }

    public function down(): void
    {
        Schema::table('talks', function (Blueprint $table) {
            $table->dropIndex(['average_rating']);
            $table->dropColumn(['average_rating', 'ratings_count']);
        });
    }
};

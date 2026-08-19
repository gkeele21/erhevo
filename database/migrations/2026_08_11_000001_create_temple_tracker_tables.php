<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reference data: dedicated temples, seeded from a JSON snapshot
        // produced by the temples:import scraper.
        Schema::create('temples', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // import upsert key, from source URL
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable(); // state/province; null for many international temples
            $table->string('country');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('photo_url', 2048)->nullable();
            // First day of the original dedication (ranges collapse to day one).
            $table->date('dedicated_on');
            $table->string('source_url', 2048);
            $table->timestamps();

            $table->index(['country', 'state']);
        });

        Schema::create('temple_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('temple_id')->constrained()->cascadeOnDelete();
            $table->date('visited_on');
            // Array of App\Enums\Ordinance values; [] means "just a visit".
            $table->json('ordinances');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'temple_id']);
            $table->index(['user_id', 'visited_on']);
        });

        Schema::create('temple_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('temple_trip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temple_trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('temple_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order');
            // Trip check-off; independent of temple_visits so planning never
            // auto-creates visit log rows.
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['temple_trip_id', 'temple_id']);
            $table->index(['temple_trip_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temple_trip_items');
        Schema::dropIfExists('temple_trips');
        Schema::dropIfExists('temple_visits');
        Schema::dropIfExists('temples');
    }
};

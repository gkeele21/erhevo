<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Who can see an item with "specific friends" (custom) visibility.
        // Polymorphic so posts and lessons/talks share one mechanism.
        Schema::create('item_shares', function (Blueprint $table) {
            $table->id();
            $table->morphs('shareable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['shareable_type', 'shareable_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_shares');
    }
};

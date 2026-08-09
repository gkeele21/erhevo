<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One AI key per provider per user (previously a single provider/key
     * pair on the users table). `users.ai_provider` is kept as the pointer
     * to the user's default provider for general AI features.
     */
    public function up(): void
    {
        Schema::create('user_ai_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);
            $table->text('api_key');
            $table->timestamps();
            $table->unique(['user_id', 'provider']);
        });

        // Move existing single connections over. Both columns use Laravel's
        // `encrypted` cast, so the ciphertext can be copied verbatim.
        DB::table('users')
            ->whereNotNull('ai_provider')
            ->whereNotNull('ai_api_key')
            ->orderBy('id')
            ->each(function ($user) {
                DB::table('user_ai_connections')->insert([
                    'user_id' => $user->id,
                    'provider' => $user->ai_provider,
                    'api_key' => $user->ai_api_key,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ai_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('ai_api_key')->nullable();
        });

        // Restore each user's default-provider key onto the users table.
        DB::table('user_ai_connections')->orderBy('id')->each(function ($conn) {
            DB::table('users')
                ->where('id', $conn->user_id)
                ->where('ai_provider', $conn->provider)
                ->update(['ai_api_key' => $conn->api_key]);
        });

        Schema::dropIfExists('user_ai_connections');
    }
};

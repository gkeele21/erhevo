<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The provider key the user connected (openai, anthropic, gemini), or null.
            $table->string('ai_provider')->nullable()->after('settings');
            // Encrypted at the model layer via the 'encrypted' cast. text() leaves
            // room for the ciphertext, which is larger than the raw key.
            $table->text('ai_api_key')->nullable()->after('ai_provider');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ai_provider', 'ai_api_key']);
        });
    }
};

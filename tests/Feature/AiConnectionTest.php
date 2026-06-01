<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_feature_is_blocked_when_no_account_is_connected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ai.suggest-tags'), [
            'content' => 'This is a journal entry about gratitude and family.',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'code' => 'ai_not_connected',
            ]);
    }

    public function test_connecting_rejects_an_unsupported_provider(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('ai-connection.update'), [
            'ai_provider' => 'skynet',
            'ai_api_key' => 'whatever-key',
        ]);

        $response->assertSessionHasErrors('ai_provider');
        $this->assertFalse($user->fresh()->hasAiConnection());
    }

    public function test_connecting_verifies_the_key_and_stores_it_encrypted(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'ok']],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('ai-connection.update'), [
            'ai_provider' => 'anthropic',
            'ai_api_key' => 'sk-ant-secret-key',
        ]);

        $response->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertTrue($fresh->hasAiConnection());
        $this->assertSame('anthropic', $fresh->ai_provider);
        $this->assertSame('sk-ant-secret-key', $fresh->ai_api_key, 'key decrypts back to plaintext');

        // The stored ciphertext must not equal the plaintext key.
        $raw = \DB::table('users')->where('id', $user->id)->value('ai_api_key');
        $this->assertNotSame('sk-ant-secret-key', $raw, 'key is stored encrypted');
    }

    public function test_connecting_rejects_an_invalid_key(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'error' => ['message' => 'invalid x-api-key'],
            ], 401),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('ai-connection.update'), [
            'ai_provider' => 'anthropic',
            'ai_api_key' => 'bad-key',
        ]);

        $response->assertSessionHasErrors('ai_api_key');
        $this->assertFalse($user->fresh()->hasAiConnection());
    }

    public function test_connected_user_can_use_an_ai_feature(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => '["gratitude","family"]']],
            ], 200),
        ]);

        $user = User::factory()->create([
            'ai_provider' => 'anthropic',
            'ai_api_key' => 'sk-ant-secret-key',
        ]);

        $response = $this->actingAs($user)->postJson(route('ai.suggest-tags'), [
            'content' => 'This is a journal entry about gratitude and family.',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'tags' => ['gratitude', 'family'],
            ]);
    }

    public function test_user_can_disconnect_their_ai_account(): void
    {
        $user = User::factory()->create([
            'ai_provider' => 'anthropic',
            'ai_api_key' => 'sk-ant-secret-key',
        ]);

        $this->assertTrue($user->hasAiConnection());

        $response = $this->actingAs($user)->delete(route('ai-connection.destroy'));

        $response->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->hasAiConnection());
    }
}

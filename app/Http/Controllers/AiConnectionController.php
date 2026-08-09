<?php

namespace App\Http\Controllers;

use App\AI\AiManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AiConnectionController extends Controller
{
    public function __construct(
        protected AiManager $aiManager,
    ) {}

    /**
     * Connect an AI account (or replace the stored key for that provider).
     * A user can hold one connection per provider; the first one connected
     * becomes their default.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ai_provider' => ['required', 'string', Rule::in($this->aiManager->availableProviders())],
            'ai_api_key' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        // Verify the key actually works before saving it.
        $test = $this->aiManager->testConnection($validated['ai_provider'], $validated['ai_api_key']);

        if (! $test['ok']) {
            throw ValidationException::withMessages([
                'ai_api_key' => 'We could not connect with that key: ' . ($test['error'] ?? 'unknown error') . '.',
            ]);
        }

        $user = $request->user();

        $user->aiConnections()->updateOrCreate(
            ['provider' => $validated['ai_provider']],
            ['api_key' => $validated['ai_api_key']],
        );

        if (! $user->ai_provider) {
            $user->ai_provider = $validated['ai_provider'];
            $user->save();
        }

        return back()->with('success', 'AI account connected.');
    }

    /**
     * Choose which connected provider general AI features use.
     */
    public function setDefault(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ai_provider' => ['required', 'string', Rule::in($this->aiManager->availableProviders())],
        ]);

        $user = $request->user();

        if (! $user->aiConnection($validated['ai_provider'])) {
            throw ValidationException::withMessages([
                'ai_provider' => 'Connect that provider first.',
            ]);
        }

        $user->ai_provider = $validated['ai_provider'];
        $user->save();

        return back()->with('success', 'Default AI provider updated.');
    }

    /**
     * Disconnect one provider. If it was the default, another remaining
     * connection (if any) becomes the default.
     */
    public function destroy(Request $request, string $provider): RedirectResponse
    {
        $user = $request->user();

        $user->aiConnections()->where('provider', $provider)->delete();
        $user->load('aiConnections');

        if ($user->ai_provider === $provider) {
            $user->ai_provider = $user->aiConnections->first()?->provider;
            $user->save();
        }

        return back()->with('success', 'AI account disconnected.');
    }
}

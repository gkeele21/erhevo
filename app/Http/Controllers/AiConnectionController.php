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
     * Connect (or replace) the user's own AI account.
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
        $user->ai_provider = $validated['ai_provider'];
        $user->ai_api_key = $validated['ai_api_key'];
        $user->save();

        return back()->with('success', 'AI account connected.');
    }

    /**
     * Disconnect the user's AI account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->ai_provider = null;
        $user->ai_api_key = null;
        $user->save();

        return back()->with('success', 'AI account disconnected.');
    }
}

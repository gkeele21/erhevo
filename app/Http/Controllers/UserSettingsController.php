<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserSettingsController extends Controller
{
    /**
     * Update the user's settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'show_lds_content' => 'sometimes|required|boolean',
            'getting_started_dismissed' => 'sometimes|required|boolean',
            'whats_new_seen_through' => 'sometimes|required|date_format:Y-m-d',
        ]);

        $user = $request->user();

        foreach ($validated as $key => $value) {
            $user->setSetting($key, $value);
        }

        $user->save();

        return back();
    }
}

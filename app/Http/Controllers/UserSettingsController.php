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
            'show_lds_content' => 'required|boolean',
        ]);

        $user = $request->user();

        foreach ($validated as $key => $value) {
            $user->setSetting($key, $value);
        }

        $user->save();

        return back();
    }
}

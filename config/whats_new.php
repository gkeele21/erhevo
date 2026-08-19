<?php

/*
|--------------------------------------------------------------------------
| What's New
|--------------------------------------------------------------------------
|
| Announcements shown on the dashboard to users who haven't seen them yet
| (newest first). Add an entry here when you ship something users should
| notice; it appears for each user until they dismiss it, tracked via the
| `whats_new_seen_through` user setting.
|
| Fields: `date` (Y-m-d — bump to today when adding, it drives "unseen"),
| `title`, `body`, and an optional `help_anchor` linking to a section id
| in the Help page.
|
*/

return [

    'entries' => [

        [
            'date' => '2026-08-11',
            'title' => 'Temple Tracker',
            'body' => 'A new Temples section for members with LDS content on: browse every dedicated temple in a list or on a map, filter by country and state, log your visits (with ordinances), explore temples near any spot, and plan trips as check-off lists.',
            'help_anchor' => 'temple-tracker',
        ],

        [
            'date' => '2026-08-09',
            'title' => 'Connect more than one AI account',
            'body' => 'Your profile now holds a key for each AI provider — Anthropic, OpenAI, and Gemini — instead of just one. Pick a default for everyday AI features; anything your default can\'t do (like transcribing a video) automatically uses another of your connected accounts.',
            'help_anchor' => 'settings',
        ],

        [
            'date' => '2026-08-09',
            'title' => 'Turn a video post into text — automatically',
            'body' => 'Paste an Instagram, TikTok, YouTube, or Facebook video link into the source-link field on a post (or a lesson quote) and hit Transcribe video. The spoken words become your text, the account that posted it is attributed as the author, and the link is saved as the source.',
            'help_anchor' => 'posts',
        ],

    ],

];

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's API key for one AI provider. A user can hold one connection per
 * provider; `users.ai_provider` names which of them is the default for
 * general AI features.
 */
class UserAiConnection extends Model
{
    protected $fillable = [
        'provider',
        'api_key',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

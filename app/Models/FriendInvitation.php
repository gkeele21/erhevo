<?php

namespace App\Models;

use App\Enums\FriendshipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FriendInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'inviter_id',
        'email',
        'token',
        'registered_user_id',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function registeredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_user_id');
    }

    public function scopePending($query)
    {
        return $query->whereNull('accepted_at');
    }

    public static function generateToken(): string
    {
        return Str::random(40);
    }

    /**
     * Mark the invitation accepted and befriend the inviter and the new user.
     */
    public function acceptFor(User $user): void
    {
        if ($this->accepted_at) {
            return;
        }

        if ($this->inviter && ! $this->inviter->isFriendWith($user->id)) {
            Friendship::create([
                'requester_id' => $this->inviter_id,
                'addressee_id' => $user->id,
                'status' => FriendshipStatus::Accepted,
            ]);
        }

        $this->update([
            'registered_user_id' => $user->id,
            'accepted_at' => now(),
        ]);
    }
}

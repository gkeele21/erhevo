<?php

namespace App\Models;

use App\Enums\FriendshipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'addressee_id',
        'status',
    ];

    protected $casts = [
        'status' => FriendshipStatus::class,
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function addressee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', FriendshipStatus::Pending);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', FriendshipStatus::Accepted);
    }

    public function accept(): bool
    {
        return $this->update(['status' => FriendshipStatus::Accepted]);
    }

    public function decline(): bool
    {
        return $this->update(['status' => FriendshipStatus::Declined]);
    }

    public function block(): bool
    {
        return $this->update(['status' => FriendshipStatus::Blocked]);
    }

    public function isPending(): bool
    {
        return $this->status === FriendshipStatus::Pending;
    }

    public function isAccepted(): bool
    {
        return $this->status === FriendshipStatus::Accepted;
    }
}

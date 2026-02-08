<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\FriendshipStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'requester_id');
    }

    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'addressee_id');
    }

    public function pendingFriendRequests(): HasMany
    {
        return $this->receivedFriendRequests()->pending();
    }

    public function friends()
    {
        $sentAccepted = $this->sentFriendRequests()
            ->accepted()
            ->pluck('addressee_id');

        $receivedAccepted = $this->receivedFriendRequests()
            ->accepted()
            ->pluck('requester_id');

        $friendIds = $sentAccepted->merge($receivedAccepted)->unique();

        return static::whereIn('id', $friendIds);
    }

    public function friendIds(): array
    {
        $sentAccepted = $this->sentFriendRequests()
            ->accepted()
            ->pluck('addressee_id');

        $receivedAccepted = $this->receivedFriendRequests()
            ->accepted()
            ->pluck('requester_id');

        return $sentAccepted->merge($receivedAccepted)->unique()->toArray();
    }

    public function isFriendWith(int $userId): bool
    {
        return in_array($userId, $this->friendIds());
    }

    public function hasPendingFriendRequestFrom(int $userId): bool
    {
        return $this->receivedFriendRequests()
            ->where('requester_id', $userId)
            ->pending()
            ->exists();
    }

    public function hasSentFriendRequestTo(int $userId): bool
    {
        return $this->sentFriendRequests()
            ->where('addressee_id', $userId)
            ->pending()
            ->exists();
    }

    public function sendFriendRequest(User $user): Friendship
    {
        return Friendship::create([
            'requester_id' => $this->id,
            'addressee_id' => $user->id,
            'status' => FriendshipStatus::Pending,
        ]);
    }

    public function removeFriend(User $user): bool
    {
        return Friendship::where(function ($query) use ($user) {
            $query->where('requester_id', $this->id)
                ->where('addressee_id', $user->id);
        })->orWhere(function ($query) use ($user) {
            $query->where('requester_id', $user->id)
                ->where('addressee_id', $this->id);
        })->delete();
    }

    public function blockUser(User $user): bool
    {
        $friendship = Friendship::where(function ($query) use ($user) {
            $query->where('requester_id', $this->id)
                ->where('addressee_id', $user->id);
        })->orWhere(function ($query) use ($user) {
            $query->where('requester_id', $user->id)
                ->where('addressee_id', $this->id);
        })->first();

        if ($friendship) {
            return $friendship->block();
        }

        Friendship::create([
            'requester_id' => $this->id,
            'addressee_id' => $user->id,
            'status' => FriendshipStatus::Blocked,
        ]);

        return true;
    }
}

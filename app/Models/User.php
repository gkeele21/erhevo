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
        'is_admin',
        'settings',
        'ai_provider',
        'ai_api_key',
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
        'ai_api_key',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'show_lds_content',
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
            'is_admin' => 'boolean',
            'settings' => 'array',
            'ai_api_key' => 'encrypted',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function userCategories(): HasMany
    {
        return $this->hasMany(UserCategory::class);
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

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a setting value.
     */
    public function setSetting(string $key, mixed $value): self
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get whether the user wants to see LDS content.
     * Defaults to true (opt-out model).
     */
    public function getShowLdsContentAttribute(): bool
    {
        return $this->getSetting('show_lds_content', true);
    }

    /**
     * Whether the user has connected their own AI account.
     */
    public function hasAiConnection(): bool
    {
        return filled($this->ai_provider) && filled($this->ai_api_key);
    }
}

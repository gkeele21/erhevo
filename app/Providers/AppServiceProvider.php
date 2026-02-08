<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Friendship;
use App\Models\Post;
use App\Policies\CategoryPolicy;
use App\Policies\FriendshipPolicy;
use App\Policies\PostPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Friendship::class, FriendshipPolicy::class);
    }
}

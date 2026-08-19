<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Friendship;
use App\Models\Lesson;
use App\Models\Post;
use App\Models\TempleTrip;
use App\Models\TempleVisit;
use App\Models\UserCategory;
use App\Policies\CategoryPolicy;
use App\Policies\FriendshipPolicy;
use App\Policies\LessonPolicy;
use App\Policies\PostPolicy;
use App\Policies\TempleTripPolicy;
use App\Policies\TempleVisitPolicy;
use App\Policies\UserCategoryPolicy;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Friendship::class, FriendshipPolicy::class);
        Gate::policy(UserCategory::class, UserCategoryPolicy::class);
        Gate::policy(TempleVisit::class, TempleVisitPolicy::class);
        Gate::policy(TempleTrip::class, TempleTripPolicy::class);

        // Trace every outgoing email so delivery problems are diagnosable:
        // one line when a send is attempted, one when the SMTP server accepts
        // it (with the message id to correlate against the provider's logs).
        Event::listen(MessageSending::class, function (MessageSending $event) {
            Log::info('Mail sending', [
                'to' => collect($event->message->getTo())->map->getAddress()->all(),
                'subject' => $event->message->getSubject(),
            ]);
        });

        Event::listen(MessageSent::class, function (MessageSent $event) {
            Log::info('Mail accepted by SMTP server', [
                'to' => collect($event->message->getTo())->map->getAddress()->all(),
                'subject' => $event->message->getSubject(),
                'message_id' => $event->sent->getMessageId(),
            ]);
        });
    }
}

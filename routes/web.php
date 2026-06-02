<?php

use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminCfmPublisherContentController;
use App\Http\Controllers\Admin\AdminCfmPublisherController;
use App\Http\Controllers\Admin\AdminCfmSpecialTopicController;
use App\Http\Controllers\Admin\AdminCfmStudyYearController;
use App\Http\Controllers\Admin\AdminCfmWeekController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostShareController;
use App\Http\Controllers\SharedPostController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AiConnectionController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TalkController;
use App\Http\Controllers\UserCategoryController;
use App\Http\Controllers\UserSettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', fn () => Inertia::render('About'))->name('about');
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

// Shared post editing (public, rate-limited)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/s/{token}/edit', [SharedPostController::class, 'edit'])->name('posts.shared.edit');
    Route::put('/s/{token}', [SharedPostController::class, 'update'])->name('posts.shared.update');
});

// Authenticated routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Dashboard
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Posts CRUD (except public index and show)
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post:slug}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post:slug}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post:slug}', [PostController::class, 'destroy'])->name('posts.destroy');

    // Post sharing
    Route::get('/posts/{post:slug}/share', [PostShareController::class, 'index'])->name('posts.share.index');
    Route::post('/posts/{post:slug}/share', [PostShareController::class, 'store'])->name('posts.share.store');
    Route::delete('/posts/{post:slug}/share/{token}', [PostShareController::class, 'destroy'])->name('posts.share.destroy');

    // Image uploads
    Route::post('/upload-image', [ImageUploadController::class, 'store'])->name('images.store');
    Route::delete('/images/{image}', [ImageUploadController::class, 'destroy'])->name('images.destroy');

    // Category suggestions
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');

    // Friends
    Route::get('/friends', [FriendshipController::class, 'index'])->name('friends.index');
    Route::post('/friends/request/{user}', [FriendshipController::class, 'sendRequest'])->name('friends.request');
    Route::post('/friends/accept/{friendship}', [FriendshipController::class, 'accept'])->name('friends.accept');
    Route::post('/friends/decline/{friendship}', [FriendshipController::class, 'decline'])->name('friends.decline');
    Route::delete('/friends/{user}', [FriendshipController::class, 'remove'])->name('friends.remove');
    Route::post('/friends/block/{user}', [FriendshipController::class, 'block'])->name('friends.block');
    Route::get('/users/search', [FriendshipController::class, 'searchUsers'])->name('users.search');

    // Tags API
    Route::get('/api/tags/search', [TagController::class, 'search'])->name('tags.search');

    // Authors API
    Route::get('/api/authors/search', [PostController::class, 'searchAuthors'])->name('authors.search');

    // User Categories
    Route::get('/my-categories', [UserCategoryController::class, 'index'])->name('user-categories.index');
    Route::post('/my-categories', [UserCategoryController::class, 'store'])->name('user-categories.store');
    Route::put('/my-categories/{userCategory}', [UserCategoryController::class, 'update'])->name('user-categories.update');
    Route::delete('/my-categories/{userCategory}', [UserCategoryController::class, 'destroy'])->name('user-categories.destroy');

    // LDS content library (gated by the user's show_lds_content setting)
    Route::get('/library', [TalkController::class, 'index'])->name('talks.index');

    // User Settings
    Route::put('/user/settings', [UserSettingsController::class, 'update'])->name('user-settings.update');

    // AI Connection (bring-your-own AI account)
    Route::put('/user/ai-connection', [AiConnectionController::class, 'update'])->name('ai-connection.update');
    Route::delete('/user/ai-connection', [AiConnectionController::class, 'destroy'])->name('ai-connection.destroy');

    // AI Features
    Route::post('/api/ai/extract-text', [AiController::class, 'extractText'])->name('ai.extract-text');
    Route::post('/api/ai/generate-excerpt', [AiController::class, 'generateExcerpt'])->name('ai.generate-excerpt');
    Route::post('/api/ai/suggest-tags', [AiController::class, 'suggestTags'])->name('ai.suggest-tags');
    Route::post('/api/ai/suggest-scriptures', [AiController::class, 'suggestScriptures'])->name('ai.suggest-scriptures');
    Route::post('/api/ai/writing-prompts', [AiController::class, 'generateWritingPrompts'])->name('ai.writing-prompts');
    Route::post('/api/ai/insights', [AiController::class, 'analyzeInsights'])->name('ai.insights');
    Route::post('/api/ai/analyze-sensitivity', [AiController::class, 'analyzeContentSensitivity'])->name('ai.analyze-sensitivity');
    Route::post('/api/ai/suggest-category', [AiController::class, 'suggestCategory'])->name('ai.suggest-category');
});

// Admin routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'admin',
])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::resource('users', AdminUserController::class)->except(['create', 'store']);
    Route::post('/users/{user}/toggle-admin', [AdminUserController::class, 'toggleAdmin'])->name('users.toggle-admin');

    // Categories
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    Route::post('/categories/{category}/approve', [AdminCategoryController::class, 'approve'])->name('categories.approve');
    Route::post('/categories/{category}/reject', [AdminCategoryController::class, 'reject'])->name('categories.reject');

    // CFM Management
    Route::resource('cfm/study-years', AdminCfmStudyYearController::class)->names('cfm.study-years');
    Route::resource('cfm/weeks', AdminCfmWeekController::class)->names('cfm.weeks');
    Route::resource('cfm/special-topics', AdminCfmSpecialTopicController::class)->names('cfm.special-topics');

    // CFM Publishers
    Route::resource('cfm/publishers', AdminCfmPublisherController::class)->names('cfm.publishers');
    Route::post('cfm/publishers/{publisher}/toggle-verified', [AdminCfmPublisherController::class, 'toggleVerified'])->name('cfm.publishers.toggle-verified');
    Route::post('cfm/publishers/{publisher}/toggle-active', [AdminCfmPublisherController::class, 'toggleActive'])->name('cfm.publishers.toggle-active');

    // CFM Publisher Content
    Route::resource('cfm/publisher-content', AdminCfmPublisherContentController::class)->except(['show'])->names('cfm.publisher-content');
    Route::post('cfm/publisher-content/{publisher_content}/toggle-featured', [AdminCfmPublisherContentController::class, 'toggleFeatured'])->name('cfm.publisher-content.toggle-featured');
});

// Post show route (must be after /posts/create to avoid slug collision)
Route::get('/posts/{post:slug}', [PostController::class, 'show'])->name('posts.show');

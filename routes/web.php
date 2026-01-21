<?php

use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', fn () => Inertia::render('About'))->name('about');
Route::get('/stories', [StoryController::class, 'index'])->name('stories.index');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

// Authenticated routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Dashboard
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Stories CRUD (except public index and show)
    Route::get('/stories/create', [StoryController::class, 'create'])->name('stories.create');
    Route::post('/stories', [StoryController::class, 'store'])->name('stories.store');
    Route::get('/stories/{story:slug}/edit', [StoryController::class, 'edit'])->name('stories.edit');
    Route::put('/stories/{story:slug}', [StoryController::class, 'update'])->name('stories.update');
    Route::delete('/stories/{story:slug}', [StoryController::class, 'destroy'])->name('stories.destroy');

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
});

// Admin routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    Route::post('/categories/{category}/approve', [AdminCategoryController::class, 'approve'])->name('categories.approve');
    Route::post('/categories/{category}/reject', [AdminCategoryController::class, 'reject'])->name('categories.reject');
});

// Story show route (must be after /stories/create to avoid slug collision)
Route::get('/stories/{story:slug}', [StoryController::class, 'show'])->name('stories.show');

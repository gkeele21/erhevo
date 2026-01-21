# Erhevo Routes

All routes are defined in `routes/web.php`.

## Public Routes

These routes are accessible without authentication.

| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/` | `home` | `HomeController@index` | Landing page |
| GET | `/about` | `about` | Closure (Inertia) | About page |
| GET | `/stories` | `stories.index` | `StoryController@index` | Browse public stories |
| GET | `/stories/{story:slug}` | `stories.show` | `StoryController@show` | View single story |

### Stories Query Parameters

The `/stories` endpoint supports the following query parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search by title, tag name, or author |
| `category` | string | Filter by category slug |
| `tag` | string | Filter by tag slug |
| `friends_only` | `1` | Show only stories from friends (requires auth) |

Example: `/stories?search=kindness&category=inspiration&friends_only=1`
| GET | `/categories` | `categories.index` | `CategoryController@index` | Browse categories |
| GET | `/categories/{category:slug}` | `categories.show` | `CategoryController@show` | Stories in category |

## Authenticated Routes

These routes require authentication via Jetstream middleware (`auth:sanctum`, `verified`).

### Dashboard

| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/dashboard` | `dashboard` | `DashboardController` | User dashboard |

### Stories CRUD

| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/stories/create` | `stories.create` | `StoryController@create` | Create story form |
| POST | `/stories` | `stories.store` | `StoryController@store` | Store new story |
| GET | `/stories/{story:slug}/edit` | `stories.edit` | `StoryController@edit` | Edit story form |
| PUT | `/stories/{story:slug}` | `stories.update` | `StoryController@update` | Update story |
| DELETE | `/stories/{story:slug}` | `stories.destroy` | `StoryController@destroy` | Delete story |

### Image Uploads

For Tiptap editor inline images.

| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| POST | `/upload-image` | `images.store` | `ImageUploadController@store` | Upload image |
| DELETE | `/images/{image}` | `images.destroy` | `ImageUploadController@destroy` | Delete image |

### Categories

| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| POST | `/categories` | `categories.store` | `CategoryController@store` | Suggest new category |

### Friends System

| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/friends` | `friends.index` | `FriendshipController@index` | Friends list page |
| POST | `/friends/request/{user}` | `friends.request` | `FriendshipController@sendRequest` | Send friend request |
| POST | `/friends/accept/{friendship}` | `friends.accept` | `FriendshipController@accept` | Accept request |
| POST | `/friends/decline/{friendship}` | `friends.decline` | `FriendshipController@decline` | Decline request |
| DELETE | `/friends/{user}` | `friends.remove` | `FriendshipController@remove` | Remove friend |
| POST | `/friends/block/{user}` | `friends.block` | `FriendshipController@block` | Block user |
| GET | `/users/search` | `users.search` | `FriendshipController@searchUsers` | Search users |

### Tags API

| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/api/tags/search` | `tags.search` | `TagController@search` | Tag autocomplete |

## Admin Routes

Prefixed with `/admin`, named with `admin.` prefix.

| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/admin/categories` | `admin.categories.index` | `AdminCategoryController@index` | List categories |
| POST | `/admin/categories` | `admin.categories.store` | `AdminCategoryController@store` | Create category |
| PUT | `/admin/categories/{category}` | `admin.categories.update` | `AdminCategoryController@update` | Update category |
| DELETE | `/admin/categories/{category}` | `admin.categories.destroy` | `AdminCategoryController@destroy` | Delete category |
| POST | `/admin/categories/{category}/approve` | `admin.categories.approve` | `AdminCategoryController@approve` | Approve suggested |
| POST | `/admin/categories/{category}/reject` | `admin.categories.reject` | `AdminCategoryController@reject` | Reject suggested |

## Jetstream Routes

Jetstream provides additional routes for:
- `/login` - Login page
- `/register` - Registration page
- `/forgot-password` - Password reset request
- `/reset-password/{token}` - Password reset form
- `/two-factor-challenge` - 2FA challenge
- `/user/profile` - Profile management
- `/user/profile-information` - Update profile info
- `/user/password` - Update password
- `/user/two-factor-authentication` - 2FA setup
- `/user/two-factor-qr-code` - 2FA QR code
- `/user/two-factor-recovery-codes` - Recovery codes
- `/logout` - Logout

## Route Model Binding

Stories and categories use slug-based route model binding:
- `{story:slug}` - Binds by `slug` column instead of `id`
- `{category:slug}` - Binds by `slug` column instead of `id`

## Route Ordering Note

The `/stories/{story:slug}` route is defined **after** `/stories/create` to prevent the wildcard from catching the literal "create" path. When adding new story routes, ensure specific paths come before wildcard routes.

## Usage Examples

```php
// Generate URLs in controllers/views
route('home');                          // /
route('stories.index');                 // /stories
route('stories.show', $story);          // /stories/{slug}
route('stories.create');                // /stories/create
route('friends.request', $user);        // /friends/request/{user_id}

// In Vue with Inertia
<Link :href="route('stories.show', story.slug)">View</Link>
<Link :href="route('dashboard')">Dashboard</Link>
```

## Controllers Location

All controllers are in `app/Http/Controllers/`:
- `HomeController.php`
- `StoryController.php`
- `CategoryController.php`
- `TagController.php`
- `FriendshipController.php`
- `ImageUploadController.php`
- `DashboardController.php`
- `Admin/AdminCategoryController.php`

# Erhevo - Database Schema

## Tables Overview

### users (Jetstream default)
Standard Laravel Jetstream users table.

### categories
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Category name |
| slug | string | URL-friendly slug |
| description | text | Category description |
| user_id | bigint (nullable) | Creator (null = admin-created) |
| is_approved | boolean | Approval status for user-suggested |
| created_at | timestamp | |
| updated_at | timestamp | |

### tags
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Tag name |
| slug | string | URL-friendly slug |
| created_at | timestamp | |
| updated_at | timestamp | |

### stories
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uuid | uuid | Public identifier |
| title | string | Story title |
| slug | string | URL-friendly slug |
| content | longText | Original rich text content |
| content_anonymized | longText (nullable) | Processed version with names replaced |
| excerpt | text (nullable) | Short summary |
| cover_image | string (nullable) | Cover image path |
| user_id | bigint | Creator (foreign key) |
| author_type | enum | 'self', 'text', 'user' |
| author_text | string (nullable) | Free text author name |
| author_user_id | bigint (nullable) | Reference to users table |
| category_id | bigint (nullable) | Foreign key to categories |
| visibility | enum | 'public', 'private', 'friends' |
| hide_creator | boolean | Hide creator name on public stories |
| hide_author | boolean | Hide author attribution |
| anonymize_names | boolean | Auto-replace names in content |
| name_mappings | json (nullable) | Stored name replacements |
| published_at | timestamp (nullable) | Publish date (null = draft) |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp (nullable) | Soft deletes |

### story_tag (pivot)
| Column | Type | Description |
|--------|------|-------------|
| story_id | bigint | Foreign key to stories |
| tag_id | bigint | Foreign key to tags |

### friendships
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| requester_id | bigint | User who sent request |
| addressee_id | bigint | User who receives request |
| status | enum | 'pending', 'accepted', 'declined', 'blocked' |
| created_at | timestamp | |
| updated_at | timestamp | |

### story_images
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| story_id | bigint (nullable) | Foreign key (nullable during upload) |
| user_id | bigint | Uploader |
| path | string | Storage path |
| filename | string | Original filename |
| mime_type | string | File MIME type |
| size | integer | File size in bytes |
| created_at | timestamp | |
| updated_at | timestamp | |

## Enums

### App\Enums\Visibility
```php
enum Visibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Friends = 'friends';
}
```

### App\Enums\AuthorType
```php
enum AuthorType: string
{
    case Self = 'self';
    case Text = 'text';
    case User = 'user';
}
```

### App\Enums\FriendshipStatus
```php
enum FriendshipStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Blocked = 'blocked';
}
```

## Model Relationships

### Story
- `belongsTo` User (creator)
- `belongsTo` User (author, optional)
- `belongsTo` Category
- `belongsToMany` Tags
- `hasMany` StoryImages

### User
- `hasMany` Stories (created)
- `hasMany` Stories (authored)
- `belongsToMany` Users (friends via friendships)

### Category
- `hasMany` Stories
- `belongsTo` User (optional, for user-suggested)

### Tag
- `belongsToMany` Stories

## Computed Attributes

The Story model includes these appended attributes (automatically included in JSON/API responses):

| Attribute | Description |
|-----------|-------------|
| `creator_name` | Creator's name (null if `hide_creator` is true) |
| `author_name` | Author attribution based on `author_type` (null if `hide_author` is true) |
| `display_content` | Returns `content_anonymized` if anonymization enabled, otherwise `content` |

# Erhevo - Project Overview

## What is Erhevo?

Erhevo is a web application for storing and sharing uplifting stories and thoughts. The name is inspired by the German verb "erheben" meaning "to lift up, elevate, or raise."

**Tagline:** "A place where words lift you."

## Tech Stack

- **Backend:** Laravel 12
- **Frontend:** Vue 3 with Inertia.js
- **Authentication:** Laravel Jetstream (with teams, 2FA, profile management)
- **Rich Text Editor:** Tiptap (Vue 3 WYSIWYG)
- **NLP:** Compromise.js (for name anonymization)
- **Database:** MySQL
- **CSS:** Tailwind CSS with custom brand colors
- **Build:** Vite 7

## Core Features

### Stories
- Create/edit/delete stories with rich text content
- Categories and tags for organization
- Cover images and inline images
- Three visibility levels: public, private, friends-only
- Privacy options for public stories:
  - Hide creator name
  - Hide author attribution
  - Auto-anonymize names in content (NLP-based)

### Author Attribution
Stories can attribute authorship three ways:
1. **Self** - The creator is the author
2. **Text** - Free text (e.g., "Maya Angelou", "Unknown", "Traditional")
3. **User** - Reference another registered user

### Friends System
- Request/accept friendship model (like Facebook)
- Friends can see each other's friends-only stories
- User search for finding friends

### Categories
- Admin-created default categories
- Users can suggest new categories (requires approval)
- Each story belongs to one category

### Tags
- Multiple tags per story
- Tag autocomplete/search
- Browse stories by tag

## Project Structure

```
erhevo/
├── app/
│   ├── Enums/           # Visibility, AuthorType, FriendshipStatus
│   ├── Http/Controllers/
│   ├── Models/          # Story, Category, Tag, Friendship, StoryImage
│   ├── Policies/        # StoryPolicy, CategoryPolicy, FriendshipPolicy
│   └── Services/        # NameAnonymizer
├── database/
│   ├── migrations/
│   └── seeders/         # CategorySeeder with 8 default categories
├── resources/js/
│   ├── Components/
│   │   └── Story/       # StoryEditor, StoryCard, TagInput, etc.
│   ├── Layouts/
│   │   └── AppLayout.vue
│   └── Pages/
│       ├── Welcome.vue
│       ├── About.vue
│       ├── Dashboard.vue
│       ├── Stories/     # Index, Create, Show, Edit
│       ├── Categories/  # Index, Show
│       └── Friends/     # Index
├── public/images/       # Brand assets and logos
└── docs/                # This documentation
```

## Database Configuration

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erhevo
DB_USERNAME=root
DB_PASSWORD=Lakers55
```

## Default Categories

1. Inspiration
2. Gratitude
3. Kindness
4. Perseverance
5. Love & Family
6. Faith & Hope
7. Personal Growth
8. Random Acts

## Test User

- Email: test@example.com
- Password: Lakers55

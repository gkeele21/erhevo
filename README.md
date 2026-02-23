# Erhevo

A faith-inspired content sharing platform that enables users to share and discover uplifting stories, thoughts, and reflections while integrating with scripture study programs.

## Overview

Erhevo creates a community space where users can:
- Share personal stories, thoughts, notes, and quotes
- Connect with friends and build networks
- Organize content by categories and tags
- Link personal reflections to scripture passages
- Discover relevant content from past years when the same scriptures are studied again (4-year cycle)
- Browse external resources from verified publishers

## Tech Stack

### Backend
- **Framework**: Laravel 12
- **PHP**: 8.4
- **Authentication**: Laravel Jetstream + Sanctum

### Frontend
- **Framework**: Vue 3 (Composition API)
- **Routing**: Inertia.js
- **Styling**: Tailwind CSS 3.4
- **Build Tool**: Vite
- **Rich Text Editor**: TipTap

## Features

### Content Management
- **4 Post Types**: Story, Thought, Note, Quote
- **Visibility Controls**: Public, Private, Friends-only
- **Privacy Options**: Hide creator identity, hide author attribution, anonymize names
- **Rich Text Editor**: Full content creation with image support
- **Draft Saving**: Save posts as drafts before publishing
- **Featured Images**: Cover image support

### Organization
- **Categories**: Admin-curated and user-suggested (requires approval)
- **Tags**: User-created tagging system with auto-complete
- **Filtering**: By type, category, tag, search terms

### Social Features
- Friend requests and management
- Accept/decline/block functionality
- Friends-only content visibility
- User search

### Come Follow Me (CFM) Integration
Deep integration with scripture study programs:
- **Scripture Database**: Volumes → Books → Chapters with verse counts
- **4-Year Study Cycle**: Book of Mormon, D&C, Old Testament, New Testament
- **Weekly Assignments**: ~52 weeks per year plus special topics (Christmas, Easter, General Conference)
- **Scripture References**: Link posts to specific scripture passages
- **Content Resurfacing**: Posts resurface when the same chapters are studied again
- **Publisher Directory**: External CFM content creators with verification status

### Admin Dashboard
- User management
- Category approval/rejection
- CFM study year management
- CFM week scheduling with chapter selection
- CFM special topics management
- Publisher and publisher content curation

## Installation

```bash
# Clone the repository
git clone <repository-url>
cd erhevo/src

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations and seed the database
php artisan migrate --seed

# Build assets
npm run build
```

## Development

```bash
# Start the development server
npm run dev

# In a separate terminal, start the Laravel server
php artisan serve
```

## Database Seeding

The application includes seeders for scripture data:

```bash
# Seed all data
php artisan db:seed

# Seed specific data
php artisan db:seed --class=ScriptureSeeder
php artisan db:seed --class=CfmScheduleSeeder
```

### Scripture Data Files
Located in `database/data/`:
- `scriptures.json` - Book of Mormon, Doctrine & Covenants, Pearl of Great Price
- `scriptures-bible.json` - Old Testament and New Testament
- `cfm-schedule-{year}.json` - CFM weekly schedules (2021-2026)

## Project Structure

```
app/
├── Http/Controllers/
│   ├── Admin/           # Admin controllers
│   ├── PostController   # Post CRUD
│   ├── CategoryController
│   ├── FriendshipController
│   └── ...
├── Models/
│   ├── Post             # User content
│   ├── Category         # Content organization
│   ├── Tag              # Tagging system
│   ├── ScriptureVolume  # Scripture hierarchy
│   ├── ScriptureBook
│   ├── ScriptureChapter
│   ├── CfmStudyYear     # CFM curriculum
│   ├── CfmWeek
│   ├── CfmPublisher     # External content
│   └── ...
resources/
├── js/
│   ├── Pages/
│   │   ├── Admin/       # Admin Vue pages
│   │   ├── Posts/       # Post management pages
│   │   ├── Friends/     # Social features
│   │   └── ...
│   ├── Components/      # Reusable Vue components
│   └── Layouts/         # Page layouts
database/
├── data/                # Scripture and CFM JSON data
├── migrations/          # Database migrations
└── seeders/             # Data seeders
```

## Key Routes

### Public
- `/` - Welcome page with featured posts
- `/posts` - Browse all public posts
- `/categories` - Browse categories
- `/about` - About page

### Authenticated
- `/dashboard` - User dashboard
- `/posts/create` - Create new post
- `/friends` - Friend management

### Admin (`/admin`)
- `/admin` - Admin dashboard
- `/admin/users` - User management
- `/admin/categories` - Category moderation
- `/admin/cfm/study-years` - Study year management
- `/admin/cfm/weeks` - Week scheduling
- `/admin/cfm/special-topics` - Special topics
- `/admin/cfm/publishers` - Publisher management
- `/admin/cfm/publisher-content` - Publisher content

## Scripture Data Coverage

### Volumes Included
- **Book of Mormon**: All 15 books (1 Nephi through Moroni)
- **Doctrine & Covenants**: Sections 1-138 + Official Declarations
- **Pearl of Great Price**: Moses, Abraham, JS-Matthew, JS-History, Articles of Faith
- **Old Testament**: All 39 books (Genesis through Malachi)
- **New Testament**: All 27 books (Matthew through Revelation)

### CFM Schedules
- 2021: Doctrine & Covenants
- 2022: Old Testament
- 2023: New Testament
- 2024: Book of Mormon
- 2025: Doctrine & Covenants
- 2026: Old Testament

## License

This project is proprietary software.

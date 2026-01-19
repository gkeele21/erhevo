# Erhevo Development Setup

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- MySQL 8.0+

## Quick Start

```bash
# Clone and enter directory
cd /Users/gkeele/dev/src/erhevo

# Install dependencies and set up project
composer setup
```

The `composer setup` command runs:
1. `composer install`
2. Copies `.env.example` to `.env` if not exists
3. Generates application key
4. Runs database migrations
5. Installs npm dependencies
6. Builds frontend assets

## Manual Setup

If you prefer manual setup:

```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env (see below)

# Run migrations
php artisan migrate

# Install JavaScript dependencies
npm install

# Build assets for production
npm run build
```

## Database Configuration

The project uses MySQL. Configure in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erhevo
DB_USERNAME=root
DB_PASSWORD=your_password
```

Create the database:
```sql
CREATE DATABASE erhevo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Running the Development Server

Use the combined dev script:

```bash
composer dev
```

This starts:
- Laravel development server (http://127.0.0.1:8000)
- Queue worker
- Log viewer (Laravel Pail)
- Vite dev server (hot reload)

Or run individually:

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite (frontend hot reload)
npm run dev

# Terminal 3 (optional): Queue worker
php artisan queue:listen
```

## Project Dependencies

### PHP (composer.json)

| Package | Version | Purpose |
|---------|---------|---------|
| laravel/framework | ^12.0 | Laravel core |
| laravel/jetstream | ^5.4 | Auth, 2FA, profile management |
| laravel/sanctum | ^4.0 | API authentication |
| inertiajs/inertia-laravel | ^2.0 | Inertia.js server adapter |
| tightenco/ziggy | ^2.0 | Laravel routes in JavaScript |

### JavaScript (package.json)

| Package | Version | Purpose |
|---------|---------|---------|
| vue | ^3.3.13 | Vue 3 framework |
| @inertiajs/vue3 | ^2.0 | Inertia.js Vue adapter |
| @tiptap/vue-3 | ^3.15.3 | WYSIWYG editor |
| @tiptap/starter-kit | ^3.15.3 | Tiptap basic extensions |
| @tiptap/extension-image | ^3.15.3 | Image support in editor |
| @tiptap/extension-link | ^3.15.3 | Link support in editor |
| @tiptap/extension-placeholder | ^3.15.3 | Placeholder text |
| compromise | ^14.14.5 | NLP for name detection |
| tailwindcss | ^3.4.0 | CSS framework |
| vite | ^7.0.7 | Build tool |

## File Storage

Configure storage for image uploads in `.env`:

```env
FILESYSTEM_DISK=public
```

Create the storage symlink:
```bash
php artisan storage:link
```

Images are stored in `storage/app/public/story-images/` and accessible via `/storage/story-images/`.

## Testing

```bash
# Run all tests
composer test

# Or directly
php artisan test

# Run specific test file
php artisan test tests/Feature/StoryTest.php
```

## Code Quality

```bash
# Format PHP code with Pint
./vendor/bin/pint

# Check specific file
./vendor/bin/pint app/Models/Story.php
```

## Common Artisan Commands

```bash
# Clear all caches
php artisan optimize:clear

# Fresh migration (drops all tables)
php artisan migrate:fresh

# Seed database
php artisan db:seed

# Create new model with migration, controller, etc.
php artisan make:model ModelName -mfc

# Create new controller
php artisan make:controller ControllerName

# List all routes
php artisan route:list
```

## Environment Variables

Key `.env` variables:

```env
APP_NAME=Erhevo
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erhevo
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public

MAIL_MAILER=log  # Change for real email
```

## Troubleshooting

### "Vite manifest not found"
```bash
npm run build
```

### "Class not found" errors
```bash
composer dump-autoload
php artisan optimize:clear
```

### Database connection errors
1. Check MySQL is running
2. Verify credentials in `.env`
3. Ensure database exists

### Storage permission errors
```bash
chmod -R 775 storage bootstrap/cache
```

### After pulling new code
```bash
composer install
npm install
php artisan migrate
npm run build
```

## IDE Setup

### VS Code Extensions
- Vue Language Features (Volar)
- Laravel Extension Pack
- Tailwind CSS IntelliSense
- PHP Intelephense

### PHPStorm
- Enable Laravel plugin
- Configure PHP interpreter
- Set up Node.js interpreter

## Test User

After seeding, a test user may be available:
- Email: `test@example.com`
- Password: `password`

(Check `database/seeders/DatabaseSeeder.php` for actual test user credentials)

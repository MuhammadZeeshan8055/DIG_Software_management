# DIG Software Management

Internal staff/admin system — Laravel 12, Blade, Alpine.js, custom CSS.

**Stack:** PHP 8.2 · Laravel 12 · Laravel Breeze (Blade) · MySQL

---

## Prerequisites

Check before starting:

```powershell
php -v          # PHP 8.2+
composer -V     # Composer installed
node -v         # Node.js (needed after Breeze)
npm -v
```

---

## Step 1 — Create Laravel 12 project

```powershell
cd D:\laravel_projects

composer create-project laravel/laravel:^12.0 DIG_Software_management
```

---

## Step 2 — Environment setup

```powershell
cd DIG_Software_management

copy .env.example .env

php artisan key:generate
```

Edit `.env` — set database (example):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dig_management_software
DB_USERNAME=root
DB_PASSWORD=
```

Create the database in phpMyAdmin or MySQL, then run migrations:

```powershell
php artisan migrate
```

---

## Step 3 — Run the app

```powershell
php artisan serve
```

Open: **http://127.0.0.1:8000**

---

## Step 4 — Install Laravel Breeze (auth)

```powershell
composer require laravel/breeze --dev

php artisan breeze:install blade

npm install

npm run build

php artisan migrate
```

### What Breeze adds

- Login, register, logout, password reset
- `routes/auth.php`
- `app/Http/Controllers/Auth/`
- `resources/views/auth/`
- Users table migration (if not already migrated)

### Test auth

```powershell
php artisan serve
```

- Register: **http://127.0.0.1:8000/register**
- Login: **http://127.0.0.1:8000/login**
- Dashboard (protected): **http://127.0.0.1:8000/dashboard**

---

## Useful commands (daily dev)

```powershell
# Start dev server
php artisan serve

# Rebuild frontend assets after CSS/JS changes
npm run dev

# Or one-off production build
npm run build

# New migration
php artisan make:migration create_example_table

# Run migrations
php artisan migrate

# Roll back last migration
php artisan migrate:rollback

# Create controller
php artisan make:controller Admin/ExampleController

# Create model + migration
php artisan make:model Example -m

# Clear caches (if something looks stale)
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## Project folders (quick reference)

| Path | Purpose |
|------|---------|
| `app/Http/Controllers` | Request handling / business logic |
| `app/Models` | Database models (Eloquent) |
| `routes/web.php` | Web routes |
| `routes/auth.php` | Auth routes (Breeze) |
| `resources/views` | Blade templates |
| `resources/css` | Styles (custom CSS later) |
| `database/migrations` | Database schema changes |
| `public/` | Web root — point cPanel here |

---

## Git — files not committed (.gitignore)

These stay local and are **not** pushed to GitHub:

- `.env` — secrets, DB password, app key
- `/vendor` — PHP dependencies (run `composer install` on server)
- `/node_modules` — JS dependencies (run `npm install` locally)
- `/public/build` — built assets (run `npm run build`)

---

## Next steps (planned)

1. Replace Breeze Tailwind views with custom CSS
2. Admin layout (sidebar)
3. Modules: quotations, ticketing, bookings, attendance, PDF import
4. GitHub + cPanel deploy

---

## PHP version note

- **PHP 8.2.12** → Laravel 12 (this project)
- Laravel 13 requires PHP 8.3+

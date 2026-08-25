# DIG Software Management

Internal staff/admin system — Laravel 12, Blade, Alpine.js, custom CSS.

**Stack:** PHP 8.2 · Laravel 12 · Laravel Breeze (Blade) · MySQL · Alpine.js (CDN) · plain CSS

---

## Prerequisites

```powershell
php -v          # PHP 8.2+
composer -V     # Composer installed
```

No Node.js or npm required.

---

## Setup

```powershell
copy .env.example .env
php artisan key:generate
```

Edit `.env` — set database, then:

```powershell
php artisan migrate
php artisan serve
```

Open: **http://127.0.0.1:8000**

---

## Assets (no build step)

| Asset | Location |
|-------|----------|
| Admin CSS | `public/css/admin.css` |
| Auth CSS | `public/css/auth.css` |
| Alpine.js | CDN (layouts) |
| Tailwind (Breeze profile pages only) | CDN |

Edit CSS under `public/css/` and refresh the browser — no `npm run build`.

---

## Useful commands

```powershell
php artisan serve
php artisan migrate
php artisan make:controller Admin/ExampleController
php artisan make:model Example -m
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## Project folders

| Path | Purpose |
|------|---------|
| `app/Http/Controllers` | Request handling |
| `app/Models` | Eloquent models |
| `routes/web.php` | Web routes |
| `routes/auth.php` | Auth routes (Breeze) |
| `resources/views` | Blade templates |
| `public/css` | Stylesheets |
| `public/` | Web root — point cPanel here |

---

## Git — not committed

- `.env` — secrets
- `/vendor` — run `composer install` on server

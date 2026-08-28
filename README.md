# DIG Software Management

Internal staff/admin system — Laravel 12, Blade, Alpine.js, custom CSS.

**Stack:** PHP 8.2 · Laravel 12 · Laravel Breeze (Blade) · Livewire · MySQL · Alpine.js (CDN) · plain CSS

**Extra packages (Import Ticket Details):** `livewire/livewire` · `smalot/pdfparser`

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

## cPanel deployment

Run from the Laravel project root (where `artisan` is).

### Install packages

If `composer.json` and `composer.lock` are already on the server:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

If you need to install the Import Ticket Details packages manually:

```bash
composer require livewire/livewire:^4.4 smalot/pdfparser:^2.12 --no-interaction
```

### After install

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Point the domain document root to the `public/` folder.

---

## Git — not committed

- `.env` — secrets
- `/vendor` — run `composer install` on server

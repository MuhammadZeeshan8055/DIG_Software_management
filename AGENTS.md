# DIG Software Management — Agent rules

## Database access (HARD RULE — never bypass)

**Default: do not touch the application database.**

Before any database write (local or live), the agent MUST:

1. Ask the user for **explicit authorization in this chat** (yes/no for that specific command).
2. Print and verify the **actual** connection target from the active `.env`:
   - `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`
3. Stop if the database name looks like production / shared hosting / live data, unless the user clearly names that exact database and authorizes the write.

### Forbidden without explicit user authorization

Never run or generate/execute scripts that do any of the following against the configured app DB:

- `php artisan migrate:fresh`
- `php artisan migrate:refresh`
- `php artisan db:wipe`
- `php artisan db:seed` (against the app DB)
- `php artisan migrate` / `migrate --force` (still needs authorization)
- `Schema::drop*` / `dropAllTables` / truncate / wipe helpers
- Eloquent/Query Builder `delete()`, `forceDelete()`, `truncate()`, `update()` bulk ops
- Raw SQL `DELETE`, `TRUNCATE`, `DROP`, `ALTER` that destroys or rewrites data
- One-off PHP/artisan tinker scripts that import, replace, or clear table rows
- Tests that hit the real application database

### Allowed without DB write authorization

- Read-only file edits (PHP, Blade, CSS, JS, docs)
- Cache-only artisan that cannot modify business tables:
  - `php artisan view:clear`
  - `php artisan config:clear`
  - `php artisan route:clear`
  - `php artisan cache:clear` (app cache store only — still avoid if unsure)
  - `php artisan optimize:clear`
- `php artisan migrate:status` (read-only status)
- Suggesting SQL / migration text for the user to run themselves

### If the user asks for a migration or data fix

1. Prefer shipping migration/code files only.
2. Tell the user the exact command to run.
3. Do **not** run it yourself unless they reply with clear authorization for that command and you have verified `DB_DATABASE`.

### Tests

Running tests that use a database requires:

1. Explicit authorization, and
2. A verified disposable database that is **not** the application database named in `.env`.

## Deploy / live server

Never assume local `.env` is safe to treat as throwaway. Never instruct or run destructive DB commands on live. Prefer: deploy code → user runs only the specific approved migrate if needed → `view:clear` / `optimize:clear` for cache.

# Renas Laravel Resource Scaffold

Interactive scaffold for Laravel with Inertia (Vue/React) or Blade:

- Migration (create table)
- Model (if missing)
- Controller (optional subfolders)
- Pages/views (Index + Create)

## Install (local path)

1) Put package here:
`packages/renas/laravel-resource-scaffold`

2) Add path repository to your Laravel app `composer.json`:

```json
"repositories": [
  { "type": "path", "url": "packages/renas/laravel-resource-scaffold" }
]
```

3) Require it:

```bash
composer require renas/laravel-resource-scaffold:dev-main
```

## Usage

Preferred command:

```bash
php artisan resource:scaffold
```

Backward-compatible command (still supported):

```bash
php artisan inertia:scaffold
php artisan generate:new-page
```

Options:

- `--dry-run` Show what would be created without writing files
- `--force` Overwrite without asking
- `--stack=inertia-vue|inertia-react|blade` Choose UI stack (default: `inertia-vue`)
- `--ts` Generate TypeScript pages (`<script setup lang="ts">` for Vue, `.tsx` for React)
- `--resource` Generate full resource controller methods
- `--pages=Index,Create,Edit,Show` Choose which pages/views to generate (default: `Index,Create`)

Examples:

```bash
# Inertia Vue (default)
php artisan resource:scaffold

# Inertia React
php artisan resource:scaffold --stack=inertia-react

# Blade
php artisan resource:scaffold --stack=blade
```

Publish package stubs for customization:

```bash
php artisan vendor:publish --tag=laravel-resource-scaffold-stubs
```

Published stubs are loaded from:

- `stubs/laravel-resource-scaffold/*` (preferred)
- `stubs/inertia-scaffold/*` (legacy fallback, still supported)
- `stubs/inertia-page-generator/*` (legacy fallback, still supported)

## Output

- `database/migrations/*_create_{table}_table.php`
- `app/Models/{Model}.php` (only if missing)
- `app/Http/Controllers/{Folder}/{Controller}.php`
- `resources/js/Pages/{Folder}/*.vue` for `--stack=inertia-vue`
- `resources/js/Pages/{Folder}/*.jsx|*.tsx` for `--stack=inertia-react`
- `resources/views/{folder}/*.blade.php` for `--stack=blade`

## Notes

Routes are not injected automatically (by design). Add manually:

```php
Route::resource('users', \App\Http\Controllers\Admin\Users\UserController::class);
```

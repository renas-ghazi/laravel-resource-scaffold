# Renas Laravel Resource Scaffold

Interactive scaffold for Laravel with Inertia (Vue/React) or Blade:

- Migration (create table)
- Model (if missing)
- Controller (optional subfolders)
- Pages/views (Index + Create by default)

## Installation

### For users (public install)

Install from Packagist:

```bash
composer require renas/laravel-resource-scaffold
```

No `repositories` config and no `packages/` folder are needed for normal users.

### If not on Packagist yet (GitHub VCS fallback)

Add this to your Laravel app `composer.json`:

```json
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/renas-ghazi/laravel-resource-scaffold"
  }
]
```

Then install:

```bash
composer require renas/laravel-resource-scaffold:dev-main
```

### For contributors (local development only)

Use `path` repositories only when developing the package locally:

```json
"repositories": [
  { "type": "path", "url": "packages/renas/laravel-resource-scaffold" }
]
```

```bash
composer require renas/laravel-resource-scaffold:dev-main
```

## Usage

Preferred command:

```bash
php artisan resource:scaffold
```

Backward-compatible commands (still supported):

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

## Release (public availability)

1. Tag a stable release:

```bash
git tag v1.0.0
git push origin v1.0.0
```

2. Submit repository to Packagist: <https://packagist.org/packages/submit>
3. Enable Packagist auto-update webhook for GitHub.

## Notes

Routes are not auto-injected (by design). Example:

```php
Route::resource('users', \App\Http\Controllers\Admin\Users\UserController::class);
```

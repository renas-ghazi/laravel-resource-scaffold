# Laravel Resource Scaffold

`renas/laravel-resource-scaffold` is an Artisan generator for Laravel resources using:

- Inertia + Vue
- Inertia + React
- Blade

It scaffolds migration, model (if missing), controller, and UI pages/views.

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12

## Installation

```bash
composer require renas/laravel-resource-scaffold
```

## Usage

```bash
php artisan resource:scaffold
```

### Options

- `--stack=inertia-vue|inertia-react|blade` UI stack (default: `inertia-vue`)
- `--pages=Index,Create,Edit,Show` pages/views to generate (default: `Index,Create`)
- `--resource` generate full resource controller methods
- `--ts` generate TypeScript pages (`.vue <script lang="ts">` / `.tsx`)
- `--dry-run` preview files without writing
- `--force` overwrite existing files without prompts

### Examples

```bash
# Inertia Vue (default)
php artisan resource:scaffold

# Inertia React + TypeScript
php artisan resource:scaffold --stack=inertia-react --ts --resource --pages=Index,Create,Edit,Show

# Blade
php artisan resource:scaffold --stack=blade --resource --pages=Index,Create,Edit,Show
```

## Stub Customization

Publish stubs:

```bash
php artisan vendor:publish --tag=laravel-resource-scaffold-stubs
```

Customize generated templates in:

- `stubs/laravel-resource-scaffold/*`

## Generated Files

Always generated:

- `database/migrations/*_create_{table}_table.php`
- `app/Models/{Model}.php` (only if missing)
- `app/Http/Controllers/{Folder}/{Controller}.php`

Stack output:

- `resources/js/Pages/{Folder}/*.vue` for `--stack=inertia-vue`
- `resources/js/Pages/{Folder}/*.jsx|*.tsx` for `--stack=inertia-react`
- `resources/views/{folder}/*.blade.php` for `--stack=blade`

## Route Example

Routes are intentionally not auto-injected.

```php
Route::resource('users', \App\Http\Controllers\Admin\Users\UserController::class);
```

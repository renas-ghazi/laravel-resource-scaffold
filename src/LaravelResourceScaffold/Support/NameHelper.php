<?php

declare(strict_types=1);

namespace Renas\LaravelResourceScaffold\Support;

use InvalidArgumentException;
use Illuminate\Support\Str;

class NameHelper
{
    public static function normalizeTable(string $table): string
    {
        $table = trim($table);
        $table = Str::of($table)->lower()->replace(' ', '_')->replace('-', '_')->toString();
        $table = preg_replace('/[^a-z0-9_]/', '', $table) ?: 'items';

        return $table;
    }

    public static function defaultModelFromTable(string $table): string
    {
        // users -> User, blog_posts -> BlogPost
        $singular = Str::singular($table);

        return Str::studly($singular);
    }

    public static function studlyController(string $controller): string
    {
        $controller = trim($controller);

        // if user typed "user" => "UserController"
        if (!Str::endsWith(Str::lower($controller), 'controller')) {
            $controller .= 'Controller';
        }

        // Ensure StudlyCase (handles Admin/UserController too)
        $controller = str_replace('\\', '/', $controller);
        $parts = array_filter(explode('/', $controller));
        $parts = array_map(fn ($p) => Str::studly($p), $parts);

        return implode('\\', $parts);
    }

    public static function normalizeFolderPath(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '' || $path === '/' || $path === '\\') {
            return '';
        }

        $path = str_replace('\\', '/', $path);
        $path = trim($path, '/');
        $path = preg_replace('#/+#', '/', $path) ?? $path;

        $segments = array_filter(explode('/', $path));
        $normalized = [];

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..') {
                throw new InvalidArgumentException('Controller folder cannot contain "." or ".." segments.');
            }

            $clean = preg_replace('/[^a-zA-Z0-9_-]/', '', $segment) ?? '';
            $clean = str_replace('-', '_', $clean);

            if ($clean === '') {
                continue;
            }

            $normalized[] = Str::studly($clean);
        }

        return implode('/', $normalized);
    }

    public static function normalizeInertiaPath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        $path = str_replace('\\', '/', $path);
        $path = trim($path, '/');
        $path = preg_replace('#/+#', '/', $path) ?? $path;

        if ($path === '') {
            return '';
        }

        $segments = array_filter(explode('/', $path));
        $normalized = [];

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..') {
                throw new InvalidArgumentException('Inertia folder cannot contain "." or ".." segments.');
            }

            if (!preg_match('/^[A-Za-z0-9_-]+$/', $segment)) {
                throw new InvalidArgumentException(
                    'Inertia folder may only contain letters, numbers, "_", "-", and "/" separators.'
                );
            }

            $normalized[] = $segment;
        }

        return implode('/', $normalized);
    }
}

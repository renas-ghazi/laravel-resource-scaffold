<?php

declare(strict_types=1);

namespace Renas\LaravelResourceScaffold\Console;

use InvalidArgumentException;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Renas\LaravelResourceScaffold\Support\NameHelper;
use Renas\LaravelResourceScaffold\Support\StubRenderer;
use RuntimeException;

class GenerateNewPageCommand extends Command
{
    protected $signature = 'generate:new-page
        {--force : Overwrite existing files without asking}
        {--dry-run : Show what would be generated without writing files}
        {--ts : Generate TypeScript pages for Inertia stacks}
        {--resource : Generate full resource controller methods}
        {--pages= : Comma-separated pages to generate (Index,Create,Edit,Show)}
        {--stack=inertia-vue : UI stack (inertia-vue, inertia-react, blade)}';

    protected $aliases = ['inertia:scaffold', 'resource:scaffold'];

    protected $description = 'Interactive scaffold: migration + model + controller + UI pages/views.';

    public function handle(Filesystem $files): int
    {
        try {
            $renderer = new StubRenderer($files);
            $useTypeScript = (bool) $this->option('ts');
            $resourceController = (bool) $this->option('resource');
            $dryRun = (bool) $this->option('dry-run');
            $force = (bool) $this->option('force');
            $pages = $this->resolvePagesOption($this->option('pages'));
            $stack = $this->resolveStackOption($this->option('stack'));

            if ($stack === 'blade' && $useTypeScript) {
                $this->warn('Ignoring --ts because stack "blade" does not use TypeScript page files.');
                $useTypeScript = false;
            }

            $this->info('Laravel Resource Scaffold');

            $tableRaw = (string) $this->ask('Give me name for database table', 'users');
            $table = NameHelper::normalizeTable($tableRaw);

            $defaultModel = NameHelper::defaultModelFromTable($table);
            $useDefaultModel = $this->confirm("Do you want default model name for '{$table}'? ({$defaultModel})", true);
            $model = $useDefaultModel ? $defaultModel : Str::studly((string) $this->ask('Give me model name', $defaultModel));

            $controllerRaw = (string) $this->ask('Give me name of Controller', $model . 'Controller');
            $controllerFull = NameHelper::studlyController($controllerRaw);
            $controllerClass = class_basename($controllerFull);

            $controllerFolderInput = (string) $this->ask(
                'Controller folder (optional). Example: Admin/Users (leave empty for none)',
                ''
            );
            $controllerFolder = NameHelper::normalizeFolderPath($controllerFolderInput);

            $defaultUiFolder = $controllerFolder !== '' ? $controllerFolder : $model;
            $uiFolderInput = (string) $this->ask(
                $this->uiFolderQuestion($stack),
                $defaultUiFolder
            );
            $uiFolder = NameHelper::normalizeInertiaPath($uiFolderInput);

            $baseNamespace = 'App\\Http\\Controllers';
            $relativeNamespace = $controllerFolder !== '' ? str_replace('/', '\\', $controllerFolder) : '';
            $controllerNamespace = $baseNamespace . ($relativeNamespace !== '' ? '\\' . $relativeNamespace : '');

            $controllerBaseDirectory = app_path('Http/Controllers');
            $controllerDirectory = $controllerBaseDirectory . ($controllerFolder !== '' ? '/' . $controllerFolder : '');
            $controllerPath = $controllerDirectory . '/' . $controllerClass . '.php';

            $uiBaseDirectory = $this->uiBaseDirectory($stack);
            $uiPagesDirectory = $uiBaseDirectory . ($uiFolder !== '' ? '/' . $uiFolder : '');

            $this->assertPathWithin($controllerDirectory, $controllerBaseDirectory, 'Controller folder');
            $this->assertPathWithin($controllerPath, $controllerBaseDirectory, 'Controller file');
            $this->assertPathWithin($uiPagesDirectory, $uiBaseDirectory, 'UI folder');

            $modelPath = app_path('Models/' . $model . '.php');
            $migrationFileName = date('Y_m_d_His') . '_create_' . $table . '_table.php';
            $migrationPath = database_path('migrations/' . $migrationFileName);

            $inertiaComponentBase = $uiFolder !== '' ? $uiFolder . '/' : '';
            $inertiaComponents = [];
            foreach (['Index', 'Create', 'Edit', 'Show'] as $page) {
                $inertiaComponents[$page] = str_replace('\\', '/', $inertiaComponentBase . $page);
            }

            $bladeViewBase = str_replace('/', '.', $uiFolder);
            if ($bladeViewBase !== '') {
                $bladeViewBase .= '.';
            }

            $routeResource = Str::kebab(Str::pluralStudly($model));

            $vars = [
                'table' => $table,
                'model' => $model,
                'modelVariable' => Str::camel($model),
                'controllerNamespace' => $controllerNamespace,
                'controllerClass' => $controllerClass,
                'inertiaIndexComponent' => $inertiaComponents['Index'],
                'inertiaCreateComponent' => $inertiaComponents['Create'],
                'inertiaEditComponent' => $inertiaComponents['Edit'],
                'inertiaShowComponent' => $inertiaComponents['Show'],
                'bladeIndexView' => $bladeViewBase . 'index',
                'bladeCreateView' => $bladeViewBase . 'create',
                'bladeEditView' => $bladeViewBase . 'edit',
                'bladeShowView' => $bladeViewBase . 'show',
                'routeResource' => $routeResource,
            ];

            $this->line('');
            $this->table(
                ['Setting', 'Value'],
                [
                    ['Table', $table],
                    ['Model', $model],
                    ['Controller', $controllerNamespace . '\\' . $controllerClass],
                    ['Stack', $stack],
                    ['Controller methods', $resourceController ? 'resource' : 'basic (index/create/store)'],
                    ['Pages', implode(', ', $pages)],
                    ['TypeScript pages', $this->typeScriptSummary($stack, $useTypeScript)],
                    ['Dry run', $dryRun ? 'yes' : 'no'],
                ]
            );

            $filesToWrite = [
                [
                    'type' => 'Migration',
                    'path' => $migrationPath,
                    'stub' => $this->resolveStubPath($files, 'migration.create_table.stub'),
                    'ensureDir' => dirname($migrationPath),
                ],
                [
                    'type' => 'Model',
                    'path' => $modelPath,
                    'stub' => $this->resolveStubPath($files, 'model.stub'),
                    'ensureDir' => dirname($modelPath),
                    'skipIfExists' => true,
                ],
                [
                    'type' => 'Controller',
                    'path' => $controllerPath,
                    'stub' => $this->resolveStubPath(
                        $files,
                        $this->resolveControllerStubFile($stack, $resourceController)
                    ),
                    'ensureDir' => dirname($controllerPath),
                ],
            ];

            foreach ($pages as $page) {
                $pagePath = $this->pageOutputPath($uiPagesDirectory, $stack, $page, $useTypeScript);

                $filesToWrite[] = [
                    'type' => $this->pageTypeLabel($stack, $page),
                    'path' => $pagePath,
                    'stub' => $this->resolveStubPath($files, $this->resolvePageStubFile($stack, $page, $useTypeScript)),
                    'ensureDir' => dirname($pagePath),
                ];
            }

            $summaryRows = [];

            foreach ($filesToWrite as $item) {
                $path = $item['path'];
                $stub = $item['stub'];
                $dir = $item['ensureDir'];
                $skipIfExists = (bool) ($item['skipIfExists'] ?? false);
                $exists = $files->exists($path);

                if ($exists && $skipIfExists && !$force) {
                    $this->warn("Skipping existing {$item['type']}: {$path}");
                    $summaryRows[] = ['skipped', $item['type'], $path];

                    continue;
                }

                if ($dryRun) {
                    $status = $exists ? 'would-overwrite' : 'would-write';
                    $this->line("[dry-run] {$status}: {$path}");
                    $summaryRows[] = [$status, $item['type'], $path];

                    continue;
                }

                if ($exists && !$force) {
                    $overwrite = $this->confirm("File exists. Overwrite? {$path}", false);
                    if (!$overwrite) {
                        $this->warn("Skipped: {$path}");
                        $summaryRows[] = ['skipped', $item['type'], $path];

                        continue;
                    }
                }

                $content = $renderer->render($stub, $vars);

                if (!$files->isDirectory($dir)) {
                    $files->makeDirectory($dir, 0755, true);
                }

                $files->put($path, $content);
                $status = $exists ? 'overwritten' : 'created';
                $summaryRows[] = [$status, $item['type'], $path];
                $this->info(ucfirst($status) . ": {$path}");
            }

            $this->line('');
            $this->info('Generation summary');
            $this->table(['Status', 'Type', 'Path'], $summaryRows);

            $this->line('');
            $this->info('Next steps');
            $this->line("Route::resource('{$routeResource}', \\{$controllerNamespace}\\{$controllerClass}::class);");

            if (!$dryRun) {
                $this->line('php artisan migrate');
            }

            if ($resourceController && (!in_array('Edit', $pages, true) || !in_array('Show', $pages, true))) {
                $this->warn('Resource controller includes edit/show methods. Add --pages=Index,Create,Edit,Show when needed.');
            }

            return self::SUCCESS;
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolvePagesOption(mixed $pagesOption): array
    {
        $defaultPages = ['Index', 'Create'];
        $value = trim((string) $pagesOption);

        if ($value === '') {
            return $defaultPages;
        }

        $allowedPages = ['Index', 'Create', 'Edit', 'Show'];
        $pages = [];

        foreach (explode(',', $value) as $rawPage) {
            $page = Str::studly(trim($rawPage));

            if ($page === '') {
                continue;
            }

            if (!in_array($page, $allowedPages, true)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid page "%s". Allowed pages: %s.', trim($rawPage), implode(', ', $allowedPages))
                );
            }

            if (!in_array($page, $pages, true)) {
                $pages[] = $page;
            }
        }

        if ($pages === []) {
            throw new InvalidArgumentException('No valid pages were provided for --pages.');
        }

        return $pages;
    }

    private function resolveStackOption(mixed $stackOption): string
    {
        $stack = Str::lower(trim((string) $stackOption));
        if ($stack === '') {
            return 'inertia-vue';
        }

        if ($stack === 'vue' || $stack === 'inertia') {
            return 'inertia-vue';
        }

        if ($stack === 'react') {
            return 'inertia-react';
        }

        $allowedStacks = ['inertia-vue', 'inertia-react', 'blade'];
        if (!in_array($stack, $allowedStacks, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid stack "%s". Allowed stacks: %s.', (string) $stackOption, implode(', ', $allowedStacks))
            );
        }

        return $stack;
    }

    private function resolveControllerStubFile(string $stack, bool $resourceController): string
    {
        if ($stack === 'blade') {
            return $resourceController ? 'controller.blade.resource.stub' : 'controller.blade.stub';
        }

        return $resourceController ? 'controller.inertia.resource.stub' : 'controller.inertia.stub';
    }

    private function resolvePageStubFile(string $stack, string $page, bool $useTypeScript): string
    {
        $normalizedPage = Str::lower($page);

        if ($stack === 'blade') {
            return 'blade.' . $normalizedPage . '.stub';
        }

        if ($stack === 'inertia-react') {
            return $useTypeScript
                ? 'inertia.' . $normalizedPage . '.react.tsx.stub'
                : 'inertia.' . $normalizedPage . '.react.jsx.stub';
        }

        return $useTypeScript
            ? 'inertia.' . $normalizedPage . '.vue.ts.stub'
            : 'inertia.' . $normalizedPage . '.vue.stub';
    }

    private function uiFolderQuestion(string $stack): string
    {
        if ($stack === 'blade') {
            return 'Give me name for your view folder under resources/views';
        }

        return 'Give me name for your inertia folder under resources/js/Pages';
    }

    private function uiBaseDirectory(string $stack): string
    {
        if ($stack === 'blade') {
            return resource_path('views');
        }

        return resource_path('js/Pages');
    }

    private function typeScriptSummary(string $stack, bool $useTypeScript): string
    {
        if ($stack === 'blade') {
            return 'n/a';
        }

        return $useTypeScript ? 'yes' : 'no';
    }

    private function pageOutputPath(string $uiDirectory, string $stack, string $page, bool $useTypeScript): string
    {
        if ($stack === 'blade') {
            return $uiDirectory . '/' . Str::lower($page) . '.blade.php';
        }

        if ($stack === 'inertia-react') {
            return $uiDirectory . '/' . $page . ($useTypeScript ? '.tsx' : '.jsx');
        }

        return $uiDirectory . '/' . $page . '.vue';
    }

    private function pageTypeLabel(string $stack, string $page): string
    {
        if ($stack === 'blade') {
            return 'Blade ' . $page;
        }

        if ($stack === 'inertia-react') {
            return 'Inertia React ' . $page;
        }

        return 'Inertia Vue ' . $page;
    }

    private function resolveStubPath(Filesystem $files, string $stubFile): string
    {
        $preferredPublishedPath = base_path('stubs/laravel-resource-scaffold/' . $stubFile);
        if ($files->exists($preferredPublishedPath)) {
            return $preferredPublishedPath;
        }

        $legacyInertiaScaffoldPath = base_path('stubs/inertia-scaffold/' . $stubFile);
        if ($files->exists($legacyInertiaScaffoldPath)) {
            return $legacyInertiaScaffoldPath;
        }

        $legacyInertiaPageGeneratorPath = base_path('stubs/inertia-page-generator/' . $stubFile);
        if ($files->exists($legacyInertiaPageGeneratorPath)) {
            return $legacyInertiaPageGeneratorPath;
        }

        return dirname(__DIR__, 2) . '/stubs/' . $stubFile;
    }

    private function assertPathWithin(string $candidatePath, string $basePath, string $label): void
    {
        $normalizedBase = rtrim($this->normalizeAbsolutePath($basePath), '/');
        $normalizedCandidate = $this->normalizeAbsolutePath($candidatePath);

        if (
            $normalizedCandidate !== $normalizedBase
            && !Str::startsWith($normalizedCandidate, $normalizedBase . '/')
        ) {
            throw new RuntimeException(sprintf('%s resolves outside the allowed base path.', $label));
        }
    }

    private function normalizeAbsolutePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $isAbsolute = str_starts_with($path, '/');
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        $normalized = implode('/', $segments);

        return $isAbsolute ? '/' . $normalized : $normalized;
    }
}

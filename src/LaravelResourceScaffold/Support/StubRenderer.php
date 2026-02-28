<?php

declare(strict_types=1);

namespace Renas\LaravelResourceScaffold\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class StubRenderer
{
    public function __construct(private Filesystem $files)
    {
    }

    public function render(string $stubPath, array $vars): string
    {
        if (!$this->files->exists($stubPath)) {
            throw new RuntimeException("Stub not found: {$stubPath}");
        }

        $content = $this->files->get($stubPath);

        foreach ($vars as $key => $value) {
            $replacement = (string) $value;
            $content = str_replace(['{{ ' . $key . ' }}', '{{' . $key . '}}'], $replacement, $content);
        }

        return $content;
    }
}

<?php

namespace Busanstu\DiffDefenderBundle\Service\Context;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ContextProvider
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
    }

    public function getContext(string $relativePath): string
    {
        $context = [];

        if (str_ends_with($relativePath, 'Controller.php')) {
            $context[] = $this->readFile('config/packages/security.yaml');
            $context[] = $this->readFile('config/routes.yaml');
            $context[] = $this->readFile('src/Component/');
        }

        if (str_ends_with($relativePath, 'composer.json')) {
            $context[] = $this->readFile('config/bundles.php');
        }

        if (str_contains($relativePath, 'Entity/')) {
            $migrationDir = $this->projectDir . '/migrations';
            $migrations = glob($migrationDir . '/*.php');

            if ($migrations && count($migrations) > 0) {
                rsort($migrations);
                $latestMigration = $migrations[0];
                $relativeMigrationPath = str_replace($this->projectDir . '/', '', $latestMigration);
                $migrationContent = $this->readFile($relativeMigrationPath, 100);

                $entityTime = filemtime($this->projectDir . '/' . $relativePath);
                $migrationTime = filemtime($latestMigration);

                if ($entityTime > $migrationTime) {
                    $context[] = ">>> SYSTEM ALERT <<<\n".
                                 "This Entity was modified AFTER the latest migration.\n" .
                                 "Check if the user forgot to run 'php bin/console make:migration'.";
                }

                $context[] = $migrationContent;
            }
        }

        return implode("\n\n", array_filter($context));
    }

    private function readFile(string $path, int $maxLines = 500): ?string
    {
        $fullPath = $this->projectDir . '/' . $path;

        if (!file_exists($fullPath)) {
            return null;
        }

        $content = file_get_contents($fullPath);
        $header = ">>> RELATED FILE: $path <<<\n";

        $lines = explode("\n", $content);
        if (count($lines) > $maxLines) {
            $content = implode("\n", array_slice($lines, 0, $maxLines)) . "\n... [truncated]...";
        }

        return $header . $content;
    }
}

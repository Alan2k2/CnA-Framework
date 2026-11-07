<?php

declare(strict_types=1);

namespace Tests\Architecture;

use CnA\Testing\TestCase;

class ArchitectureTest extends TestCase
{
    public function testContractsNeverDependOnConcreteAdapters(): void
    {
        $contractsDir = __DIR__ . '/../../packages/contracts/src';
        $files = $this->getPhpFiles($contractsDir);

        $forbiddenReferences = [
            'CnA\Database\Connection',
            'CnA\Database\ConnectionManager',
            'CnA\Http\Request',
            'CnA\Http\Response',
            'CnA\Container\Container',
            'CnA\Cache\CacheManager',
            'CnA\Queue\QueueManager',
            'CnA\Ai\AiManager',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            foreach ($forbiddenReferences as $forbidden) {
                $this->assertFalse(
                    str_contains((string) $contents, $forbidden),
                    "Forbidden dependency [{$forbidden}] found in contract file [{$file}]"
                );
            }
        }
    }

    public function testDomainEntitiesDoNotDependOnHttpOrDatabaseConnections(): void
    {
        $domainDir = __DIR__ . '/../../skeleton/app/Domain';
        if (!is_dir($domainDir)) {
            return;
        }

        $files = $this->getPhpFiles($domainDir);
        $forbidden = [
            'CnA\Contracts\Http\RequestInterface',
            'CnA\Contracts\Http\ResponseInterface',
            'CnA\Contracts\Database\ConnectionInterface',
            'PDO',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            foreach ($forbidden as $f) {
                $this->assertFalse(
                    str_contains((string) $contents, $f),
                    "Domain layer file [{$file}] must not depend directly on transport or database driver [{$f}]"
                );
            }
        }
    }

    protected function getPhpFiles(string $dir): array
    {
        if (!is_dir($dir)) return [];
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $item) {
            if ($item->isFile() && str_ends_with($item->getFilename(), '.php')) {
                $files[] = $item->getPathname();
            }
        }
        return $files;
    }
}


<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Testing\TestRunner;

class TestCommand extends Command
{
    protected string $name = 'test';
    protected string $description = 'Run the application and framework test suite';

    public function handle(): int
    {
        $dir = $this->argument(0, $this->app->basePath('tests'));
        if (!is_dir($dir)) {
            $dir = $this->app->basePath('tests');
        }

        return TestRunner::runDirectory($dir);
    }
}


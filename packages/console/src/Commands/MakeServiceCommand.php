<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class MakeServiceCommand extends Command
{
    protected string $name = 'make:service';
    protected string $description = 'Create a new application use case service';

    public function handle(): int
    {
        $name = $this->argument(0);
        if (!$name) {
            $this->error('Service name is required.');
            return Command::INVALID;
        }

        $className = str_ends_with($name, 'Service') ? $name : $name . 'Service';
        $path = $this->app->basePath("app/Application/Services/{$className}.php");

        if (file_exists($path)) {
            $this->error("Service [{$className}] already exists.");
            return Command::FAILURE;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $template = <<<PHP
<?php

declare(strict_types=1);

namespace App\Application\Services;

use CnA\Contracts\Database\TransactionManagerInterface;

final class {$className}
{
    public function __construct(
        private TransactionManagerInterface \$transactions
    ) {
    }

    public function execute(mixed \$command): mixed
    {
        return \$this->transactions->run(function () use (\$command) {
            // Coordinate domain operations and repositories here
            return \$command;
        });
    }
}
PHP;

        file_put_contents($path, $template);
        $this->info("Application Service [{$className}] created successfully.");
        return Command::SUCCESS;
    }
}


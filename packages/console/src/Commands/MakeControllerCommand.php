<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class MakeControllerCommand extends Command
{
    protected string $name = 'make:controller';
    protected string $description = 'Create a new thin transport controller class';

    public function handle(): int
    {
        $name = $this->argument(0);
        if (!$name) {
            $this->error('Controller name is required.');
            return Command::INVALID;
        }

        $className = str_ends_with($name, 'Controller') ? $name : $name . 'Controller';
        $path = $this->app->basePath("app/Http/Controllers/{$className}.php");

        if (file_exists($path)) {
            $this->error("Controller [{$className}] already exists.");
            return Command::FAILURE;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $template = <<<PHP
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;
use CnA\Http\JsonResponse;

class {$className}
{
    public function index(RequestInterface \$request): ResponseInterface
    {
        return new JsonResponse(['message' => '{$className} index']);
    }
}
PHP;

        file_put_contents($path, $template);
        $this->info("Controller [{$className}] created successfully.");
        return Command::SUCCESS;
    }
}


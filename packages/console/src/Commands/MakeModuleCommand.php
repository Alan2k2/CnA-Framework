<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class MakeModuleCommand extends Command
{
    protected string $name = 'make:module';
    protected string $description = 'Scaffold a new modular architecture domain (Controllers, Models, Services, Routes)';

    public function handle(): int
    {
        $name = $this->argument(0);
        if (empty($name)) {
            $this->error('Module name is required. Usage: php cna make:module <Name>');
            return Command::INVALID;
        }

        $studly = ucfirst($name);
        $baseDir = $this->app->basePath("app/Modules/{$studly}");

        if (is_dir($baseDir)) {
            $this->warn("Module {$studly} already exists at {$baseDir}");
            return Command::FAILURE;
        }

        mkdir("{$baseDir}/Controllers", 0755, true);
        mkdir("{$baseDir}/Models", 0755, true);
        mkdir("{$baseDir}/Services", 0755, true);

        // 1. Controller
        $controllerContent = <<<PHP
<?php

declare(strict_types=1);

namespace App\Modules\\{$studly}\\Controllers;

use CnA\Http\Response;

class {$studly}Controller
{
    public function index(): Response
    {
        return Response::json([
            'module' => '{$studly}',
            'status' => 'operational'
        ]);
    }
}

PHP;
        file_put_contents("{$baseDir}/Controllers/{$studly}Controller.php", $controllerContent);

        // 2. Model
        $modelContent = <<<PHP
<?php

declare(strict_types=1);

namespace App\Modules\\{$studly}\\Models;

use CnA\Database\Model;

class {$studly} extends Model
{
    protected string \$table = '{$name}s';
}

PHP;
        file_put_contents("{$baseDir}/Models/{$studly}.php", $modelContent);

        // 3. Service
        $serviceContent = <<<PHP
<?php

declare(strict_types=1);

namespace App\Modules\\{$studly}\\Services;

class {$studly}Service
{
    public function execute(): array
    {
        return ['status' => 'executed'];
    }
}

PHP;
        file_put_contents("{$baseDir}/Services/{$studly}Service.php", $serviceContent);

        // 4. Routes
        $routeContent = <<<PHP
<?php

declare(strict_types=1);

/** @var \CnA\Routing\Router \$router */

\$router->get('/api/{$name}s', [App\Modules\\{$studly}\\Controllers\\{$studly}Controller::class, 'index']);

PHP;
        file_put_contents("{$baseDir}/routes.php", $routeContent);

        $this->info("Domain module [{$studly}] created successfully!");
        $this->info("Directory: {$baseDir}");
        $this->info("Created: Controller, Model, Service, and Routes.");
        return Command::SUCCESS;
    }
}


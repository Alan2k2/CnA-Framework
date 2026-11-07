<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class MakeRepositoryCommand extends Command
{
    protected string $name = 'make:repository';
    protected string $description = 'Create a new domain repository interface and SQL infrastructure implementation';

    public function handle(): int
    {
        $name = $this->argument(0);
        if (!$name) {
            $this->error('Repository name is required.');
            return Command::INVALID;
        }

        $base = str_replace('Repository', '', $name);
        $interfaceName = "{$base}RepositoryInterface";
        $implName = "Sql{$base}Repository";

        $interfacePath = $this->app->basePath("app/Domain/Repositories/{$interfaceName}.php");
        $implPath = $this->app->basePath("app/Infrastructure/Repositories/{$implName}.php");

        // Interface
        if (!is_dir(dirname($interfacePath))) {
            mkdir(dirname($interfacePath), 0755, true);
        }
        $interfaceTemplate = <<<PHP
<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface {$interfaceName}
{
    public function find(int|string \$id): mixed;
    public function save(mixed \$entity): void;
}
PHP;
        file_put_contents($interfacePath, $interfaceTemplate);

        // Implementation
        if (!is_dir(dirname($implPath))) {
            mkdir(dirname($implPath), 0755, true);
        }
        $implTemplate = <<<PHP
<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\\{$interfaceName};
use CnA\Contracts\Database\ConnectionInterface;

class {$implName} implements {$interfaceName}
{
    public function __construct(protected ConnectionInterface \$connection)
    {
    }

    public function find(int|string \$id): mixed
    {
        return \$this->connection->table(strtolower('{$base}s'))->where('id', \$id)->first();
    }

    public function save(mixed \$entity): void
    {
        // Persistence logic
    }
}
PHP;
        file_put_contents($implPath, $implTemplate);

        $this->info("Repository interface [{$interfaceName}] and SQL adapter [{$implName}] created successfully.");
        return Command::SUCCESS;
    }
}


<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class MakeModelCommand extends Command
{
    protected string $name = 'make:model';
    protected string $description = 'Create a new ORM Model class';

    public function handle(): int
    {
        $name = $this->argument(0);
        if (!$name) {
            $this->error('Model name is required.');
            return Command::INVALID;
        }

        $path = $this->app->basePath("app/Models/{$name}.php");
        if (file_exists($path)) {
            $this->error("Model [{$name}] already exists.");
            return Command::FAILURE;
        }

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $template = <<<PHP
<?php

declare(strict_types=1);

namespace App\Models;

use CnA\Orm\Model;

class {$name} extends Model
{
    protected array \$fillable = [
        //
    ];
}
PHP;

        file_put_contents($path, $template);
        $this->info("Model [{$name}] created successfully.");
        return Command::SUCCESS;
    }
}


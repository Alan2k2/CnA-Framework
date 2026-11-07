<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class MakeMigrationCommand extends Command
{
    protected string $name = 'make:migration';
    protected string $description = 'Create a new database migration file';

    public function handle(): int
    {
        $name = $this->argument(0);
        if (!$name) {
            $this->error('Migration name is required.');
            return Command::INVALID;
        }

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";
        $path = $this->app->basePath("database/migrations/{$fileName}");

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $tableName = 'table_name';
        if (preg_match('/create_(.*)_table/', $name, $matches)) {
            $tableName = $matches[1];
        }

        $template = <<<PHP
<?php

declare(strict_types=1);

use CnA\Migrations\Blueprint;
use CnA\Migrations\Migration;
use CnA\Migrations\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;

        file_put_contents($path, $template);
        $this->info("Created migration: {$fileName}");
        return Command::SUCCESS;
    }
}


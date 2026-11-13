<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class MakePluginCommand extends Command
{
    protected string $name = 'make:plugin';
    protected string $description = 'Scaffold a new modular plugin package (plugin.php, Provider, Composer manifest)';

    public function handle(): int
    {
        $name = $this->argument(0);
        if (empty($name)) {
            $this->error('Plugin name is required. Usage: php cna make:plugin <vendor/name|name>');
            return Command::INVALID;
        }

        $parts = explode('/', $name);
        $shortName = end($parts);
        $studly = ucfirst(str_replace(['-', '_'], '', ucwords($shortName, '-_')));
        $pluginDir = $this->app->basePath("plugins/{$shortName}");

        if (is_dir($pluginDir)) {
            $this->warn("Plugin directory already exists at: {$pluginDir}");
            return Command::FAILURE;
        }

        mkdir("{$pluginDir}/src", 0755, true);

        // 1. Plugin Class
        $pluginClassContent = <<<PHP
<?php

declare(strict_types=1);

namespace CnA\Plugins\\{$studly};

use CnA\Contracts\Core\ApplicationInterface;
use CnA\Plugins\BasePlugin;

class {$studly}Plugin extends BasePlugin
{
    protected string \$name = '{$name}';
    protected string \$version = '1.0.0';
    protected string \$description = 'Dynamic {$studly} plugin for CnA Framework';

    public function register(ApplicationInterface \$app): void
    {
        // Register bindings and singleton services
    }

    public function boot(ApplicationInterface \$app): void
    {
        // Bootstrap event listeners, routes, or workflows
    }
}

PHP;
        file_put_contents("{$pluginDir}/src/{$studly}Plugin.php", $pluginClassContent);

        // 2. plugin.php loader
        $loaderContent = <<<PHP
<?php

declare(strict_types=1);

require_once __DIR__ . '/src/{$studly}Plugin.php';

return new \CnA\Plugins\\{$studly}\\{$studly}Plugin();

PHP;
        file_put_contents("{$pluginDir}/plugin.php", $loaderContent);

        // 3. composer.json
        $composerContent = <<<JSON
{
    "name": "{$name}",
    "description": "CnA Framework plugin for {$studly}",
    "type": "cna-plugin",
    "autoload": {
        "psr-4": {
            "CnA\\\\Plugins\\\\{$studly}\\\\": "src/"
        }
    }
}
JSON;
        file_put_contents("{$pluginDir}/composer.json", $composerContent . "\n");

        $this->info("Plugin package [{$name}] scaffolded successfully!");
        $this->info("Directory: {$pluginDir}");
        $this->info("Entry point: {$pluginDir}/plugin.php");
        return Command::SUCCESS;
    }
}

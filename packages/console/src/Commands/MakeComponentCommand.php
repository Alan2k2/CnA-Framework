<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class MakeComponentCommand extends Command
{
    protected string $name = 'make:component';
    protected string $description = 'Generate a server-rendered PHP UI component or view template';

    public function handle(): int
    {
        $name = $this->argument(0);
        if (!$name) {
            $this->error("Please specify the component name, e.g. `php cna make:component UserProfile`");
            return Command::INVALID;
        }

        $className = ucfirst(trim($name));
        if (!str_ends_with($className, 'Component')) {
            $className .= 'Component';
        }

        $targetDir = $this->app->basePath('app/View/Components');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filePath = $targetDir . '/' . $className . '.php';
        if (file_exists($filePath)) {
            $this->warn("Component [{$className}] already exists at {$filePath}");
            return Command::FAILURE;
        }

        $alias = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', str_replace('Component', '', $className)));

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace App\View\Components;

use CnA\View\Components\BaseComponent;

class {$className} extends BaseComponent
{
    protected function defaultProps(): array
    {
        return [
            'title' => 'Default Title',
            'class' => '',
        ];
    }

    public function render(): string
    {
        \$title = \$this->e(\$this->get('title'));
        \$class = \$this->e(\$this->get('class'));

        return <<<HTML
<div class="cna-component cna-{$alias} p-4 rounded-xl border border-slate-800 bg-slate-900/60 {\$class}">
    <h4 class="text-base font-semibold text-cyan-400">{\$title}</h4>
</div>
HTML;
    }
}
PHP;

        file_put_contents($filePath, $code . PHP_EOL);

        $this->info("Component [{$className}] created successfully!");
        $this->line("File: {$filePath}");
        $this->line("\nTo register and use in your views:");
        $this->line("  app(\\CnA\\View\\Components\\ComponentRegistry::class)->register('ui.{$alias}', \\App\\View\\Components\\{$className}::class);");
        $this->line("  <?= component('ui.{$alias}', ['title' => 'Hello World']) ?>");

        return Command::SUCCESS;
    }
}


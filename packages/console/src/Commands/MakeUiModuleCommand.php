<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class MakeUiModuleCommand extends Command
{
    protected string $name = 'make:ui-module';
    protected string $description = 'Scaffold a new frontend UI feature module with TypeScript and styles';

    public function handle(): int
    {
        $name = $this->argument(0);
        if (!$name) {
            $this->error("Please specify the module name, e.g. `php cna make:ui-module dashboard`");
            return Command::INVALID;
        }

        $moduleName = strtolower(trim($name));
        $moduleDir = $this->app->basePath('resources/modules/' . $moduleName);

        if (is_dir($moduleDir)) {
            $this->warn("UI module [{$moduleName}] already exists at {$moduleDir}");
            return Command::FAILURE;
        }

        mkdir($moduleDir . '/components', 0755, true);

        // 1. index.ts
        $indexContent = <<<TS
// CnA UI Module: {$moduleName}
import './{$moduleName}.css';

export interface {$moduleName}Config {
    endpoint?: string;
    mountId?: string;
}

export function init{$moduleName}(config: {$moduleName}Config = {}): void {
    const mountId = config.mountId || 'cna-{$moduleName}';
    const container = document.getElementById(mountId);
    if (!container) {
        console.warn(`[CnA Module] Mount point #\${mountId} not found`);
        return;
    }

    console.log(`[CnA Module] Initialized module [{$moduleName}] at #\${mountId}`);
}

// Auto-boot if container is present on page
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('cna-{$moduleName}')) {
        init{$moduleName}();
    }
});
TS;
        file_put_contents($moduleDir . '/index.ts', $indexContent . PHP_EOL);

        // 2. CSS file
        $cssContent = <<<CSS
/* Styles for {$moduleName} UI Module */
.cna-module-{$moduleName} {
    padding: 1.5rem;
    border-radius: 0.75rem;
    border: 1px solid var(--cna-border, #1e293b);
    background-color: var(--cna-surface, #0f172a);
}
CSS;
        file_put_contents($moduleDir . "/{$moduleName}.css", $cssContent . PHP_EOL);

        $this->info("UI Module [{$moduleName}] scaffolded successfully!");
        $this->line("Created files:");
        $this->line("  - resources/modules/{$moduleName}/index.ts");
        $this->line("  - resources/modules/{$moduleName}/{$moduleName}.css");
        $this->line("  - resources/modules/{$moduleName}/components/");
        $this->line("\nTo register as a dedicated Vite entry point in \033[36mconfig/frontend.php\033[0m:");
        $this->line("  'entry_points' => [");
        $this->line("      '{$moduleName}' => 'resources/modules/{$moduleName}/index.ts',");
        $this->line("  ]");
        $this->line("\nAnd render in your PHP view:");
        $this->line("  <div id=\"cna-{$moduleName}\"></div>");
        $this->line("  <?= \\CnA\\Frontend\\Facades\\Vite::assets('{$moduleName}') ?>");

        return Command::SUCCESS;
    }
}

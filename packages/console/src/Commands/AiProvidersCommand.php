<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Ai\AiManager;
use CnA\Console\Command;

class AiProvidersCommand extends Command
{
    protected string $name = 'ai:providers';
    protected string $description = 'List all configured AI providers and active status';

    public function handle(): int
    {
        $this->info("CnA AI Subsystem Providers & Models\n");

        /** @var AiManager $ai */
        $ai = $this->app->has(AiManager::class) ? $this->app->get(AiManager::class) : null;
        if ($ai === null) {
            $this->error("AI subsystem not active.");
            return Command::FAILURE;
        }

        $activeProvider = $ai->provider();
        $allowedModels = config('ai.allowed_models', []);
        $defaultProvider = config('ai.default', 'fake');

        $this->table(['Setting', 'Value'], [
            ['Active Provider', $activeProvider->getName()],
            ['Default Provider in Config', $defaultProvider],
            ['Embeddings Dimension', (string) (method_exists($activeProvider, 'getDimension') ? $activeProvider->getDimension() : 'N/A')],
            ['Registered Tools Count', (string) count($ai->tools()->getSchemas())],
            ['Allowed Models', implode(', ', (array) $allowedModels)],
        ]);

        return Command::SUCCESS;
    }
}

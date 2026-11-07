<?php

declare(strict_types=1);

namespace CnA\Core\Providers;

use CnA\Ai\AiManager;
use CnA\Contracts\Ai\AIProviderInterface;
use CnA\Core\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiManager::class, function () {
            $config = (array) config('ai', []);
            $default = (string) config('ai.default', 'fake');
            return new AiManager($config, $default);
        });

        $this->app->alias(AiManager::class, 'ai');

        $this->app->bind(AIProviderInterface::class, function () {
            /** @var AiManager $ai */
            $ai = $this->app->get(AiManager::class);
            return $ai->provider();
        });
    }
}


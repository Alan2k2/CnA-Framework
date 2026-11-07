<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Security\Encrypter;

class KeyGenerateCommand extends Command
{
    protected string $name = 'key:generate';
    protected string $description = 'Set the application encryption key';

    public function handle(): int
    {
        $key = Encrypter::generateKey();
        $envPath = $this->app->basePath('.env');

        if (!file_exists($envPath)) {
            $example = $this->app->basePath('.env.example');
            if (file_exists($example)) {
                copy($example, $envPath);
            } else {
                file_put_contents($envPath, "APP_KEY=\n");
            }
        }

        $content = file_get_contents($envPath);
        if ($content !== false) {
            if (str_contains($content, 'APP_KEY=')) {
                $content = preg_replace('/^APP_KEY=.*$/m', "APP_KEY={$key}", $content);
            } else {
                $content .= PHP_EOL . "APP_KEY={$key}";
            }
            file_put_contents($envPath, $content);
        }

        $this->info("Application key set successfully: [{$key}]");
        return Command::SUCCESS;
    }
}


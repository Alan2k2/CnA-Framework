<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class ServeCommand extends Command
{
    protected string $name = 'serve';
    protected string $description = 'Start the CnA local development server';

    public function handle(): int
    {
        $host = (string) $this->option('host', '127.0.0.1');
        $port = (int) $this->option('port', 8000);

        // Locate public directory
        $publicDir = $this->app->basePath('public');
        if (!is_dir($publicDir)) {
            $publicDir = $this->app->basePath('skeleton/public');
        }

        if (!is_dir($publicDir)) {
            $this->error("Public directory not found at [{$publicDir}].");
            return Command::FAILURE;
        }

        $this->info("Starting CnA development server at: \033[36mhttp://{$host}:{$port}\033[0m");
        $this->comment("Public directory: {$publicDir}");
        $this->comment("Press Ctrl+C to stop the server.\n");

        $php = PHP_BINARY;
        $serverRouter = $this->app->basePath('server.php');
        if (!file_exists($serverRouter)) {
            $serverRouter = $this->app->basePath('skeleton/server.php');
        }

        if (file_exists($serverRouter)) {
            $command = "\"{$php}\" -S {$host}:{$port} -t \"{$publicDir}\" \"{$serverRouter}\"";
        } else {
            $command = "\"{$php}\" -S {$host}:{$port} -t \"{$publicDir}\"";
        }

        passthru($command, $status);

        return $status === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}

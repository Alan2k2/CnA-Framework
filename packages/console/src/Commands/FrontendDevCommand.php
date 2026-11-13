<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Contracts\Frontend\DevServerResolverInterface;

class FrontendDevCommand extends Command
{
    protected string $name = 'frontend:dev';
    protected string $description = 'Inspect and guide the Vite development server on loopback';

    public function handle(): int
    {
        $this->info("CnA Frontend Development Server Assistant\n");

        /** @var DevServerResolverInterface $devServer */
        $devServer = $this->app->make(DevServerResolverInterface::class);

        if ($devServer->isRunning()) {
            $this->info("Vite development server is already running and reachable at:");
            $this->line("  \033[32m" . $devServer->getUrl() . "\033[0m");
            $this->line("\nYour PHP views will automatically load scripts and styles from this Vite server with Hot Module Replacement (HMR).");
            return Command::SUCCESS;
        }

        $this->warn("Vite development server is not currently running on " . $devServer->getUrl());
        $this->line("\nTo start Vite in development mode, run:");
        $this->line("  \033[36mnpm run dev\033[0m  or  \033[36mnpx vite\033[0m\n");
        $this->line("Then in another terminal window, keep your CnA application running:");
        $this->line("  \033[36mphp cna serve\033[0m");

        return Command::SUCCESS;
    }
}


<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class ConfigShowCommand extends Command
{
    protected string $name = 'config:show';
    protected string $description = 'Display application configuration values with sensitive data masked';

    public function handle(): int
    {
        $key = $this->argument(0);
        $config = $this->app->has('config') ? $this->app->make('config') : null;

        if ($config === null) {
            $this->error('Config repository not available.');
            return Command::FAILURE;
        }

        if ($key) {
            $val = $config->get($key);
            $this->info("Configuration for '{$key}':");
            $this->dumpValue($key, $val);
            return Command::SUCCESS;
        }

        $all = $config->all();
        $this->info("Active Application Configurations:\n");

        foreach ($all as $group => $values) {
            $this->info("[$group]");
            $this->dumpValue($group, $values, 2);
        }

        return Command::SUCCESS;
    }

    protected function dumpValue(string $parentKey, mixed $val, int $indent = 0): void
    {
        $pad = str_repeat(' ', $indent);
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $subKey = "{$parentKey}.{$k}";
                if ($this->isSensitive($k)) {
                    $this->line("{$pad}{$k}: ********");
                } elseif (is_array($v)) {
                    $this->line("{$pad}{$k}:");
                    $this->dumpValue($subKey, $v, $indent + 2);
                } else {
                    $valStr = is_bool($v) ? ($v ? 'true' : 'false') : (string) $v;
                    $this->line("{$pad}{$k}: {$valStr}");
                }
            }
        } else {
            if ($this->isSensitive($parentKey)) {
                $this->line("{$pad}********");
            } else {
                $valStr = is_bool($val) ? ($val ? 'true' : 'false') : (string) $val;
                $this->line("{$pad}{$valStr}");
            }
        }
    }

    protected function isSensitive(string|int $key): bool
    {
        $lower = strtolower((string) $key);
        foreach (['key', 'secret', 'password', 'token', 'auth', 'private'] as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }
        return false;
    }
}

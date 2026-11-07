<?php

declare(strict_types=1);

namespace CnA\Console;

use CnA\Contracts\Core\ApplicationInterface;

abstract class Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;

    protected string $name = '';
    protected string $description = '';

    protected array $arguments = [];
    protected array $options = [];

    public function __construct(protected ApplicationInterface $app)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    abstract public function handle(): int;

    public function run(array $arguments, array $options): int
    {
        $this->arguments = $arguments;
        $this->options = $options;
        return $this->handle();
    }

    public function argument(int|string $key, mixed $default = null): mixed
    {
        return $this->arguments[$key] ?? $default;
    }

    public function option(string $key, mixed $default = false): mixed
    {
        return $this->options[$key] ?? $default;
    }

    public function hasOption(string $key): bool
    {
        return isset($this->options[$key]);
    }

    public function line(string $message): void
    {
        echo $message . PHP_EOL;
    }

    public function info(string $message): void
    {
        echo "\033[32m" . $message . "\033[0m" . PHP_EOL;
    }

    public function comment(string $message): void
    {
        echo "\033[33m" . $message . "\033[0m" . PHP_EOL;
    }

    public function warn(string $message): void
    {
        echo "\033[43;30m WARN \033[0m " . $message . PHP_EOL;
    }

    public function error(string $message): void
    {
        echo "\033[41;37m ERROR \033[0m " . $message . PHP_EOL;
    }

    public function table(array $headers, array $rows): void
    {
        // Calculate max width per column
        $colWidths = [];
        foreach ($headers as $i => $h) {
            $colWidths[$i] = mb_strlen((string) $h);
        }
        foreach ($rows as $row) {
            foreach (array_values($row) as $i => $val) {
                $colWidths[$i] = max($colWidths[$i] ?? 0, mb_strlen((string) $val));
            }
        }

        // Header
        $headerParts = [];
        foreach ($headers as $i => $h) {
            $headerParts[] = str_pad((string) $h, $colWidths[$i] + 2);
        }
        $this->line(implode('|', $headerParts));

        // Separator
        $sepParts = [];
        foreach ($colWidths as $w) {
            $sepParts[] = str_repeat('-', $w + 2);
        }
        $this->line(implode('+', $sepParts));

        // Rows
        foreach ($rows as $row) {
            $rowParts = [];
            foreach (array_values($row) as $i => $val) {
                $rowParts[] = ' ' . str_pad((string) $val, ($colWidths[$i] ?? 0) + 1);
            }
            $this->line(implode('|', $rowParts));
        }
    }
}


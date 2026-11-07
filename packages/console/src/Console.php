<?php

declare(strict_types=1);

namespace CnA\Console;

use CnA\Contracts\Core\ApplicationInterface;

class Console
{
    /** @var array<string, Command> */
    protected array $commands = [];

    public function __construct(protected ApplicationInterface $app)
    {
    }

    public function register(Command $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    public function run(array $argv): int
    {
        array_shift($argv); // Remove script name

        if (empty($argv) || in_array($argv[0], ['list', 'help', '--help', '-h'], true)) {
            $this->printHelp();
            return Command::SUCCESS;
        }

        if (in_array($argv[0], ['--version', '-v', '-V'], true)) {
            echo "CnA Framework " . $this->app->version() . " (PHP " . PHP_VERSION . ")" . PHP_EOL;
            return Command::SUCCESS;
        }

        $commandName = array_shift($argv);
        if (!isset($this->commands[$commandName])) {
            echo "\033[41;37m ERROR \033[0m Command [{$commandName}] is not defined." . PHP_EOL;
            return Command::INVALID;
        }

        [$arguments, $options] = $this->parseInput($argv);

        $command = $this->commands[$commandName];
        return $command->run($arguments, $options);
    }

    protected function printHelp(): void
    {
        echo "\033[36m" . <<<LOGO
   ____           ___ 
  / ___|_ __     / _ \ 
 | |   | '_ \   / /_\ \
 | |___| | | | / /_ _\ \
  \____|_| |_|/_/    \_\ Framework
LOGO . "\033[0m" . PHP_EOL;
        echo "\033[33mCnA Framework\033[0m version \033[32m" . $this->app->version() . "\033[0m" . PHP_EOL . PHP_EOL;
        echo "\033[33mUsage:\033[0m" . PHP_EOL;
        echo "  command [options] [arguments]" . PHP_EOL . PHP_EOL;
        echo "\033[33mAvailable commands:\033[0m" . PHP_EOL;

        // Group commands by namespace
        $grouped = [];
        foreach ($this->commands as $name => $cmd) {
            $parts = explode(':', $name, 2);
            $group = count($parts) === 2 ? $parts[0] : 'general';
            $grouped[$group][$name] = $cmd->getDescription();
        }

        ksort($grouped);

        foreach ($grouped as $group => $cmds) {
            echo " \033[33m" . $group . "\033[0m" . PHP_EOL;
            foreach ($cmds as $name => $desc) {
                echo "  \033[32m" . str_pad($name, 28) . "\033[0m " . $desc . PHP_EOL;
            }
        }
    }

    protected function parseInput(array $argv): array
    {
        $arguments = [];
        $options = [];

        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--')) {
                $opt = substr($arg, 2);
                if (str_contains($opt, '=')) {
                    [$k, $v] = explode('=', $opt, 2);
                    $options[$k] = $v;
                } else {
                    $options[$opt] = true;
                }
            } elseif (str_starts_with($arg, '-')) {
                $options[substr($arg, 1)] = true;
            } else {
                $arguments[] = $arg;
            }
        }

        return [$arguments, $options];
    }
}


<?php

declare(strict_types=1);

namespace CnA\Contracts\Database;

interface ConnectionManagerInterface
{
    public function connection(?string $name = null): ConnectionInterface;
    public function getDefaultConnection(): string;
    public function setDefaultConnection(string $name): void;
    public function reconnect(?string $name = null): ConnectionInterface;
    public function disconnect(?string $name = null): void;
}


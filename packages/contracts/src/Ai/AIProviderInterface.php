<?php

declare(strict_types=1);

namespace CnA\Contracts\Ai;

interface AIProviderInterface
{
    public function getName(): string;
    public function chat(array $messages, array $options = []): array;
    public function stream(array $messages, callable $callback, array $options = []): void;
}


<?php

declare(strict_types=1);

namespace CnA\Contracts\Ai;

interface EmbeddingProviderInterface
{
    public function embed(string|array $input, array $options = []): array;
    public function getDimension(): int;
}


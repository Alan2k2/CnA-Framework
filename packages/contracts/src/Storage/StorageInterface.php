<?php

declare(strict_types=1);

namespace CnA\Contracts\Storage;

interface StorageInterface
{
    public function get(string $path): ?string;
    public function put(string $path, string $contents, array $options = []): bool;
    public function exists(string $path): bool;
    public function delete(string|array $paths): bool;
    public function size(string $path): int;
    public function lastModified(string $path): int;
    public function mimeType(string $path): ?string;
    public function url(string $path): string;
    public function temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): string;
}


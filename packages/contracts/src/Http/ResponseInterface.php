<?php

declare(strict_types=1);

namespace CnA\Contracts\Http;

interface ResponseInterface
{
    public function getStatusCode(): int;
    public function withStatus(int $code, string $reasonPhrase = ''): static;
    public function getHeaders(): array;
    public function hasHeader(string $name): bool;
    public function getHeader(string $name): array;
    public function getHeaderLine(string $name): string;
    public function withHeader(string $name, string|array $value): static;
    public function withAddedHeader(string $name, string|array $value): static;
    public function withoutHeader(string $name): static;
    public function getBody(): string;
    public function withBody(string $body): static;
    public function send(): void;
}


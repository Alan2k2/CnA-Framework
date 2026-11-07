<?php

declare(strict_types=1);

namespace CnA\Ai\DTO;

class ChatMessage
{
    public function __construct(
        public readonly string $role,
        public readonly string $content,
        public readonly array $metadata = []
    ) {
    }

    public static function system(string $content, array $metadata = []): static
    {
        return new static('system', $content, $metadata);
    }

    public static function user(string $content, array $metadata = []): static
    {
        return new static('user', $content, $metadata);
    }

    public static function assistant(string $content, array $metadata = []): static
    {
        return new static('assistant', $content, $metadata);
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
            'metadata' => $this->metadata,
        ];
    }
}


<?php

declare(strict_types=1);

namespace CnA\Ai\DTO;

class ChatResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $role = 'assistant',
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly int $totalTokens = 0,
        public readonly ?string $model = null,
        public readonly array $raw = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'role' => $this->role,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->totalTokens,
            'model' => $this->model,
        ];
    }
}


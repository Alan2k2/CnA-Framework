<?php

declare(strict_types=1);

namespace CnA\Ai\Providers;

use CnA\Ai\DTO\ChatMessage;
use CnA\Ai\DTO\ChatResponse;
use CnA\Contracts\Ai\AIProviderInterface;
use CnA\Contracts\Ai\EmbeddingProviderInterface;
use CnA\Contracts\Ai\TokenCounterInterface;

class FakeAIProvider implements AIProviderInterface, EmbeddingProviderInterface, TokenCounterInterface
{
    /** @var array<array{messages: array, options: array}> */
    protected array $recordedChats = [];

    /** @var array<string> */
    protected array $cannedResponses = [];

    protected int $dimension = 1536;

    public function __construct(array $cannedResponses = [])
    {
        $this->cannedResponses = !empty($cannedResponses) ? $cannedResponses : [];
    }

    public function setCannedResponses(array $responses): static
    {
        $this->cannedResponses = $responses;
        return $this;
    }

    public function getName(): string
    {
        return 'fake';
    }

    public function addCannedResponse(string $response): static
    {
        $this->cannedResponses[] = $response;
        return $this;
    }

    public function chat(array $messages, array $options = []): array
    {
        $this->recordedChats[] = [
            'messages' => $messages,
            'options' => $options,
        ];

        $content = !empty($this->cannedResponses)
            ? array_shift($this->cannedResponses)
            : 'Fake default response';

        $totalTokens = $this->count($content) + 10;

        return [
            'content' => $content,
            'role' => 'assistant',
            'prompt_tokens' => 10,
            'completion_tokens' => $this->count($content),
            'total_tokens' => $totalTokens,
            'model' => $options['model'] ?? 'fake-model-v1',
        ];
    }

    public function stream(array $messages, callable $callback, array $options = []): void
    {
        $chat = $this->chat($messages, $options);
        $words = explode(' ', $chat['content']);
        foreach ($words as $word) {
            $callback($word . ' ');
        }
    }

    public function embed(string|array $input, array $options = []): array
    {
        $inputs = (array) $input;
        $embeddings = [];

        foreach ($inputs as $text) {
            // Generate deterministic vector based on text hash
            $hash = md5($text);
            $vector = [];
            for ($i = 0; $i < $this->dimension; $i++) {
                $vector[] = (hexdec(substr($hash, ($i % 30), 2)) / 255.0) - 0.5;
            }
            $embeddings[] = $vector;
        }

        return is_array($input) ? $embeddings : $embeddings[0];
    }

    public function getDimension(): int
    {
        return $this->dimension;
    }

    public function count(string $text, ?string $model = null): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }

    public function recordedChats(): array
    {
        return $this->recordedChats;
    }

    public function assertChatCount(int $expected): void
    {
        if (count($this->recordedChats) !== $expected) {
            throw new \RuntimeException("Expected {$expected} chat calls, but got " . count($this->recordedChats));
        }
    }
}

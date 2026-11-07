<?php

declare(strict_types=1);

namespace CnA\Ai;

use CnA\Ai\Cost\CostTracker;
use CnA\Ai\DTO\ChatMessage;
use CnA\Ai\DTO\ChatResponse;
use CnA\Ai\Providers\FakeAIProvider;
use CnA\Ai\Rag\VectorStore;
use CnA\Ai\Safety\PromptGuard;
use CnA\Ai\Tools\ToolRegistry;
use CnA\Contracts\Ai\AIProviderInterface;
use CnA\Contracts\Ai\EmbeddingProviderInterface;

class AiManager
{
    /** @var array<string, AIProviderInterface> */
    protected array $providers = [];
    protected ToolRegistry $tools;
    protected VectorStore $vectorStore;
    protected CostTracker $costTracker;

    public function __construct(
        protected array $config = [],
        protected string $default = 'fake'
    ) {
        $this->tools = new ToolRegistry();
        $this->vectorStore = new VectorStore();
        $this->costTracker = new CostTracker($config['allowed_models'] ?? []);
    }

    public function provider(?string $name = null): AIProviderInterface
    {
        $name = $name ?? $this->default;
        if (!isset($this->providers[$name])) {
            $this->providers[$name] = $this->createProvider($name);
        }
        return $this->providers[$name];
    }

    public function chat(array $messages, array $options = [], ?string $tenantId = null): ChatResponse
    {
        // Sanitize messages
        $sanitizedMessages = [];
        foreach ($messages as $msg) {
            if ($msg instanceof ChatMessage) {
                $sanitizedMessages[] = [
                    'role' => $msg->role,
                    'content' => PromptGuard::sanitizePrompt($msg->content),
                ];
            } elseif (is_array($msg)) {
                $sanitizedMessages[] = [
                    'role' => $msg['role'] ?? 'user',
                    'content' => PromptGuard::sanitizePrompt($msg['content'] ?? ''),
                ];
            }
        }

        $provider = $this->provider();
        $raw = $provider->chat($sanitizedMessages, $options);

        $response = new ChatResponse(
            $raw['content'] ?? '',
            $raw['role'] ?? 'assistant',
            $raw['prompt_tokens'] ?? 0,
            $raw['completion_tokens'] ?? 0,
            $raw['total_tokens'] ?? 0,
            $raw['model'] ?? null,
            $raw
        );

        if ($tenantId !== null) {
            $this->costTracker->recordUsage($tenantId, $response->model ?? 'default', $response->totalTokens);
        }

        return $response;
    }

    public function tools(): ToolRegistry
    {
        return $this->tools;
    }

    public function vectorStore(): VectorStore
    {
        return $this->vectorStore;
    }

    public function costTracker(): CostTracker
    {
        return $this->costTracker;
    }

    protected function createProvider(string $name): AIProviderInterface
    {
        return match ($name) {
            'fake' => new FakeAIProvider(),
            default => new FakeAIProvider(),
        };
    }
}


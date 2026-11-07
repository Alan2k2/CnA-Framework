# ADR 0007: Provider-Independent AI Subsystem

## Status
Accepted

## Context
Generative AI and LLMs are becoming critical for modern applications, but embedding vendor-specific SDKs directly into business code creates severe vendor lock-in, unmonitored costs, and security risks (prompt injection, data leakage).

## Decision
1. AI is treated as an explicit, pluggable application capability behind framework contracts (`AIProviderInterface`, `EmbeddingProviderInterface`, `TokenCounterInterface`).
2. Include a `FakeAIProvider` for deterministic offline testing with recorded chat assertions.
3. Enforce strict security boundaries:
   - `PromptGuard`: Sanitizes prompts and redacts secrets/credentials prior to transmission.
   - `CostTracker`: Enforces tenant token quotas and approved model allowlists.
   - Tool execution requires explicit schemas, permissions, timeouts, and produces structured audit logs.
   - No unrestricted shell or filesystem access is ever granted to AI agents by default.


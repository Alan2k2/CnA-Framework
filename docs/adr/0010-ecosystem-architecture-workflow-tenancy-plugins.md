# ADR 0010: Ecosystem Architecture — Workflows, Multi-Tenancy, Feature Flags, Plugins, and DevTools

## Status
Accepted

## Date
2026-11-13

## Context
As detailed in the *CnA FRAMEWORK: Complete Ecosystem Architecture - Technical Blueprint & Implementation Roadmap* (v1.0), CnA Framework transitions from a standalone web framework into a **Complete Modular Application Platform**. The runtime core remains lean, robust, and zero-runtime-dependency, surrounded by independent capability packages adhering to the contract-driven design principle.

## Decisions

### 1. Workflow Engine (`cna/workflow`)
- **State Machine & Orchestration**: Workflows coordinate sequential or conditional steps (`WorkflowStepInterface`) with explicit context propagation.
- **Saga Compensation**: When a step encounters an unhandled exception or aborts, previously executed steps are compensated in reverse order to ensure atomic rollback of distributed side-effects.
- **Observability**: Execution history and step latency metrics are recorded (`workflow:list`, `workflow:run`, `workflow:inspect`).

### 2. Multi-Tenancy (`cna/multitenancy`)
- **Context Isolation**: Multi-tenancy is modeled via `TenantInterface` and `TenantManagerInterface`.
- **Resolution Strategies**: Tenants can be resolved via subdomain/host, incoming HTTP headers (`X-Tenant-ID`), or path routing.
- **Lifecycle Guarantees**: `TenantMiddleware` sets the active tenant before request processing and strictly clears it in a `finally` block to prevent tenant cross-contamination across persistent worker daemons.

### 3. Feature Flags (`cna/feature-flags`)
- **Multi-Strategy Evaluation**: Supports boolean flags, closure-based targeting (e.g. user tier or beta group), and deterministic percentage rollouts (0-100%) hashed via CRC32 against tenant/user identifiers.
- **Global Helper**: Exposed via `feature(string $name, array $context = []): bool`.

### 4. Dynamic Plugin System (`cna/plugin-manager`)
- **Discovery & Lifecycle**: Plugins inherit from `BasePlugin` implementing `PluginInterface`.
- **Isolation**: Plugins discover manifests under `plugins/` and participate in two-phase bootstrapping: `register(ApplicationInterface $app)` for bindings and `boot(ApplicationInterface $app)` for runtime hooks.
- **Scaffolding**: Fast creation via `php cna make:plugin <name>`.

### 5. Telemetry & DevTools (`cna/devtools` & `cna/admin`)
- **OpenTelemetry-Compatible Tracing**: `TracerInterface` captures trace spans, start/end timestamps, latency in milliseconds, and contextual attributes.
- **Correlation Propagation**: `CorrelationMiddleware` intercepts or generates `X-Correlation-ID` and `X-Trace-ID`, setting them on outbound responses.
- **Admin Studio**: `AdminStudio` gathers cross-subsystem telemetry (plugins, tenants, workflows, features, memory, PHP runtime) for operational dashboards.

### 6. Extended Security Engine (`packages/security/`)
- **Comprehensive Auditing**: `SecurityAuditor` performs deep audits for Composer dependencies/constraints, OWASP ASVS HTTP security headers, and filesystem permissions.
- **Operational CLI Commands**: `security:audit`, `security:dependencies`, `security:headers`, `security:permissions`, and `security:report --format=json`.

### 7. Modular Domain Architecture
- **Domain Modules**: Scaffold complete modular domains via `php cna make:module <Name>`, creating cohesive controllers, models, services, and route definitions.

## Consequences
- The framework now provides turnkey support for enterprise multi-tenant SaaS, distributed transaction workflows, dynamic feature rollouts, and extensible plugins without bloating the core runtime.
- Monorepo package count expands to 37 decoupled packages, all verified by the test suite (91 passing tests) with zero external runtime dependencies.

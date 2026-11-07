# ADR 0008: Semantic Versioning and Deprecation Policy

## Status
Accepted

## Context
Enterprise adopters require predictable upgrade paths and API stability guarantees.

## Decision
1. Follow Semantic Versioning 2.0.0 (`MAJOR.MINOR.PATCH`).
2. Deprecations are introduced in minor versions with `@deprecated` docblocks and runtime notices in development mode.
3. Breaking changes are only introduced in major versions, accompanied by exhaustive migration guides and automated upgrade command tools where feasible.


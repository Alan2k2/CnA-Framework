# ADR 0002: Monorepo vs Split Packages Architecture

## Status
Accepted

## Context
Developers need a cohesive developer experience resembling Laravel's ergonomics, but enterprise applications demand modularity where infrastructure and transport implementations can be replaced or decoupled without monolithic runtime baggage.

## Decision
Develop CnA Framework as a Composer-compatible monorepo containing granular, decoupled packages under `packages/*` and a reference turnkey application under `skeleton/*`. Every package maintains its own `composer.json` and internal namespace boundaries (`CnA\Contracts`, `CnA\Container`, `CnA\Database`, `CnA\Ai`, etc.).

## Alternatives Considered
- Monolithic single package (`cna/framework` all in `src/`): Hinders modular extraction, leads to tight coupling between HTTP transport and domain logic.
- Completely separate git repositories from day 1: High coordination overhead for atomic framework refactoring and releases.

## Consequences
- Clean dependency inversion: Domain and application layers depend only on `cna/contracts`.
- Subsystems can be released together or split into independent read-only package mirrors.
- Clear static architecture verification.

## Security Impact
- Principle of least privilege: consumers only register the service providers they actually need.


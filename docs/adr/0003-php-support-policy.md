# ADR 0003: PHP Support Policy (PHP 8.4+)

## Status
Accepted

## Context
PHP 8.4 introduces modern language improvements including property hooks, asymmetric visibility, typed constants, new array functions, and standard performance enhancements. Supporting legacy PHP versions incurs technical debt and compromises type safety.

## Decision
CnA Framework targets PHP 8.4+ as its runtime baseline. We publish a strict PHP support policy tracking PHP release cycles: supporting actively maintained versions and providing a deprecation runway before dropping support for end-of-life PHP versions.

## Consequences
- High type safety with native union types, intersection types, readonly properties, and modern attributes.
- Native Argon2id password hashing and modern cryptographic APIs.
- Codebase remains lean and modern without polyfills or legacy compatibility layers.


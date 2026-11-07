# ADR 0006: Cache and Queue Architecture Priorities

## Status
Accepted

## Context
Applications run in diverse environments: local development, continuous integration, single VMs, and horizontally scaled container clusters.

## Decision
1. Cache drivers prioritize `array` (in-memory test fake), `file` (local disk with locks), `database`, and `redis`.
2. Queue drivers prioritize `sync` (synchronous execution for tests/dev), `database` (transactional job storage), and `redis`.
3. Queue workers feature exponential backoff, maximum retry attempts, and automatic failure archiving to `failed_jobs`.
4. Idempotency is recommended for all queued jobs.


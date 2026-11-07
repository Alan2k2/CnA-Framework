# ADR 0005: Transaction Boundaries and Layered Architecture

## Status
Accepted

## Context
Frameworks that bind transactions to HTTP middleware or let repositories manage transactions arbitrarily risk leaked transactions, deadlocks, and hidden database state during serialization.

## Decision
1. Application Services (use cases) explicitly own transaction boundaries using `TransactionManagerInterface->run(callable)`.
2. Repositories return domain entities or read models and do not initiate transactions.
3. Controllers handle transport concerns only: validate HTTP input, invoke application service command, and serialize the response.
4. Transient deadlocks are safely retried with exponential backoff up to configured attempts.


# ADR 0001: PDO as the First Database Foundation

## Status
Accepted

## Context
CnA Framework requires a robust, high-performance, and vendor-portable database abstraction supporting MySQL, PostgreSQL, and SQLite with strict security controls against SQL injection.

## Decision
We adopt PHP's native PDO (PHP Data Objects) abstraction as the unified baseline across all supported drivers. All statements will be executed as prepared statements with typed value bindings. Identifiers (tables and columns) will be validated and quoted using driver-specific SQL dialects (`SqliteDialect`, `MysqlDialect`, `PostgresDialect`).

## Alternatives Considered
- Direct driver libraries (`mysqli`, `pg_connect`): Exposes disparate APIs and complicates transaction savepoint management.
- External ORM dependencies (Doctrine DBAL): Introduces large third-party transitive dependency trees and violates the standalone modularity goal.

## Consequences
- Single, consistent database connection lifecycle across engines.
- Full support for savepoints and nested transactions.
- Zero external runtime dependencies.

## Security Impact
- 100% elimination of SQL injection through parameter binding and dialect identifier quoting allowlists.
- Sensitive binding parameters (passwords, tokens) are redacted in query logs.

## Migration Plan
Future database drivers (e.g. distributed SQL or Spanner) will implement `ConnectionInterface` and `DialectInterface`.


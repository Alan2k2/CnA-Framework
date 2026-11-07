# ADR 0004: ORM Lazy-Loading Policy & N+1 Prevention

## Status
Accepted

## Context
Implicit lazy loading in traditional ORMs (e.g. `$order->items` inside a loop) causes severe N+1 query amplification in production, leading to database degradation and unbounded latency.

## Decision
1. In local and testing environments, strict mode is enabled by default (`Model::preventLazyLoading(true)`). Any attempt to access an unloaded relation dynamically throws a `LazyLoadingViolationException` with actionable guidance recommending eager loading `with(['relation'])`.
2. Eager loading is performed via constrained queries in $O(1)$ round trips.
3. The framework includes a `QueryObserver` that monitors query fingerprints and flags repeated relation queries with different foreign keys.
4. The framework will NOT automatically rewrite arbitrary SQL into JOINs or eager loads, as rewriting can alter cardinality and authorization semantics. Instead, actionable diagnostics and recommended fixes are generated.

## Security Impact
- Eliminates resource starvation and denial-of-service risks caused by runaway database queries.


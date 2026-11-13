# ADR 0009: Frontend Engine & Vite Integration Subsystem

## Status
Accepted (Version 2.0.0 Milestone)

## Date
2026-11-13

## Context
CnA Framework was originally built as a contract-driven modular PHP backend framework with lightweight server-rendered HTML views. As applications grow in complexity, modern applications require:
1. Fast local development workflows with Hot Module Replacement (HMR) for TypeScript, CSS, and modern frontend assets.
2. Robust, production-grade build pipelines that fingerprint assets, optimize chunks, and produce a deployment-safe manifest.
3. Accessible, zero-dependency server-rendered UI components for progressive enhancement without forcing a single-page application (SPA) architecture.
4. Clean boundary separation between PHP business logic and frontend tooling, allowing optional React and Vue integrations while keeping the core framework lean.

## Decision
We adopt the **CnA Frontend Engine** as a first-class subsystem for CnA Framework Version 2.0.0, structured into three distinct layers:

### 1. CnA View Engine (`cna/view`)
- **PHP View Engine**: Provides template discovery, layout management (`layout('layouts.app')`), named sections (`section()`, `yield_section()`), partial includes, and shared view state.
- **Accessible Server-Rendered UI Components**: A built-in catalogue of accessible UI components (`ui.button`, `ui.alert`, `ui.badge`, `ui.card`, `ui.input`, `ui.modal`, `ui.table`) rendered via `component($name, $props)`.
- **Safe HTML Escaping**: Automatic output escaping via `HtmlEscaper` with explicit opt-in for raw HTML.

### 2. CnA Frontend Integration (`cna/frontend`)
- **Vite Asset Pipeline**: Vite is the official frontend development and build tool. PHP does not bundle Node.js and does not shell out to Node.js during HTTP requests.
- **Development vs. Production Separation**:
  - In development: Queries the local loopback Vite dev server (`http://127.0.0.1:5173`) and emits `@vite/client` and source entry points for instant HMR.
  - In production: Loads the compiled Vite manifest (`public/build/.vite/manifest.json`), resolves version-hashed assets, and emits optimized CSS and `modulepreload` tags.
- **Strict Security Controls**:
  - *Path Traversal Protection*: Rejects `..` traversal, absolute paths, and symlink escapes outside `public/build`.
  - *Dev Server Loopback Binding*: Strict loopback host verification (`127.0.0.1` / `localhost`) to prevent remote exposure.
  - *CSP Nonce Propagation*: Supports passing CSP nonces into `Vite::assets('app', $nonce)`.
  - *CSRF Protection*: Native `Vite::csrf()` meta tag generation.

### 3. UI Modules & Framework Adapters
- **UI Feature Modules**: Feature-oriented modular directories under `resources/modules/{name}/` containing `index.ts`, styles, and components.
- **Optional React & Vue Adapters**: Provided via `CnA\FrontendReact\ReactAdapter` and `CnA\FrontendVue\VueAdapter` for declarative DOM container mounting (`data-cna-react-root`, `data-cna-vue-root`).

## Consequences
- **Positive**: Blazing-fast frontend DX with Vite HMR; accessible server-rendered components out of the box; zero frontend coupling in the core HTTP kernel; deployment-safe manifest caching.
- **Operational Rule**: PHP and Vite remain separate processes. Node.js is required only at build time, not in production PHP runtimes.


<p align="center">
  <img src="public/images/cna-main-logo.png" alt="CnA Framework Logo" width="540">
</p>

<p align="center">
  <strong>The Contract-Driven PHP Framework • Version 2.1</strong><br>
  <em>The Complete Modular Application Platform • Secure • Scalable • Intelligent</em>
</p>

<p align="center">
  <a href="https://packagist.org/packages/alan2k2/cna-framework"><img src="https://img.shields.io/packagist/v/alan2k2/cna-framework.svg?style=flat-square" alt="Latest Packagist Version"></a>
  <a href="https://packagist.org/packages/alan2k2/cna-framework"><img src="https://img.shields.io/packagist/dt/alan2k2/cna-framework.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://alan2k2.github.io/CnA-Framework/"><img src="https://img.shields.io/badge/Live%20Demo-GitHub%20Pages-38bdf8.svg?style=flat-square" alt="Live Demo"></a>
  <a href="https://github.com/Alan2k2/CnA-Framework/actions"><img src="https://img.shields.io/badge/CI-Passing-34d399.svg?style=flat-square" alt="CI Status"></a>
  <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.4%2B-818cf8.svg?style=flat-square" alt="PHP Version"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-38bdf8.svg?style=flat-square" alt="License"></a>
  <a href="https://owasp.org/www-project-application-security-verification-standard/"><img src="https://img.shields.io/badge/OWASP%20ASVS-Level%202%20Compliant-34d399.svg?style=flat-square" alt="OWASP ASVS"></a>
  <a href="https://vite.dev/"><img src="https://img.shields.io/badge/Frontend-Vite%20Engine-06b6d4.svg?style=flat-square" alt="Vite Engine"></a>
</p>

---

## 🌟 About CnA Framework (Version 2.1)

**CnA Framework** is a contract-driven, high-performance PHP 8.4+ **Complete Modular Application Platform** built for mission-critical web applications, high-throughput APIs, modern interactive user interfaces, multi-tenant SaaS systems, distributed transaction workflows, and background worker daemons. It pairs the beloved developer ergonomics of Laravel with strict architectural boundaries, native OWASP ASVS Level 2 defenses, zero-leak dependency injection, built-in $O(1)$ eager loading with N+1 query prevention, a provider-independent AI subsystem, a **first-class Vite-powered Frontend Engine**, and an **Enterprise Modular Ecosystem**.

### 👨‍💻 Framework Architect & Lead Developer
- **Author**: **Alan Shaju** (Senior Software Engineer)
- **LinkedIn**: [https://www.linkedin.com/in/alanshaju2k2/](https://www.linkedin.com/in/alanshaju2k2/)
- **GitHub**: [https://github.com/Alan2k2](https://github.com/Alan2k2)

---

## ⚡ Quick Start & Running the Welcome Interface

### 🌐 Live Online Demo (No Installation Required)
You can directly assess the framework UI, features, commands, and interactive API sandbox online:
👉 **[https://alan2k2.github.io/CnA-Framework/](https://alan2k2.github.io/CnA-Framework/)**

---

### Local Installation & Running:

### 1. Clone & Setup
```bash
git clone https://github.com/Alan2k2/CnA-Framework.git
cd CnA-Framework
```

### 2. Environment Setup
```bash
cp .env.example .env
php cna key:generate
```

### 3. Start the Development Server
```bash
php cna serve
```
*(or on Windows: `cna serve`)*

### 4. Access the Welcome Interface
Open your browser and navigate to:
👉 **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

---

## 🚀 Key Ecosystem Subsystems (Version 2.1)

1. **Workflow Engine (`cna/workflow`)**:
   - Sequential, conditional, and parallel state machines with context immutability.
   - **Saga Pattern Compensation**: Automatic reverse-order compensation execution when any step fails.
   - Observability: Complete step duration metrics and audit trails (`workflow:list`, `workflow:run`, `workflow:inspect`).

2. **Multi-Tenancy Subsystem (`cna/multitenancy`)**:
   - Scoped tenant isolation via `TenantInterface` and `TenantManagerInterface`.
   - Resolution strategies: HTTP headers (`X-Tenant-ID`), subdomains, or URI paths.
   - Zero-leak lifecycle: Automatic context clearing in `TenantMiddleware` finally blocks.

3. **Feature Flags Engine (`cna/feature-flags`)**:
   - Multi-strategy evaluation: Booleans, custom closure targeting, and CRC32-based percentage rollouts (0-100%).
   - Global helper: `feature('flag_name', ['user_id' => 123])`.

4. **Dynamic Plugin Architecture (`cna/plugin-manager`)**:
   - Two-phase bootstrap: `register()` for container bindings and `boot()` for runtime events.
   - Rapid scaffolding: `php cna make:plugin <name>`.

5. **Telemetry & OpenTelemetry Tracing (`cna/devtools` & `cna/admin`)**:
   - Microsecond-accurate span tracking and latency instrumentation.
   - Transparent correlation ID propagation (`X-Correlation-ID`, `X-Trace-ID`).
   - Admin Studio diagnostic status aggregation.

6. **Extended Security Auditor (`packages/security/`)**:
   - Deep audits for Composer dependencies, constraints, and lockfiles.
   - OWASP ASVS HTTP security headers inspection (CSP, HSTS, X-Frame-Options, X-Content-Type-Options).
   - Filesystem permission validation and structured JSON exports (`security:report --format=json`).

7. **Vite Frontend Engine & Accessible UI (`cna/frontend` & `cna/view`)**:
   - Dual-mode Vite support: Dev HMR loopback and production `.vite/manifest.json` reader.
   - Server-rendered accessible UI component library (`ui.button`, `ui.alert`, `ui.badge`, `ui.card`, `ui.input`, `ui.modal`, `ui.table`).
   - Optional React and Vue mounting adapters.

---

## 📂 Monorepo Structure (37 Decoupled Packages)

```
cna-framework/
├── cna                        # CLI executable script
├── cna.bat                    # Windows native CLI launcher
├── server.php                 # Dev server rewrite router
├── vite.config.js             # Vite asset pipeline configuration
├── package.json               # Frontend dependencies & scripts
├── composer.json              # Monorepo composer configuration
├── .env.example               # Environment template
├── packages/                  # 37 Modular Packages
│   ├── contracts/             # Core interfaces (Workflow, Tenancy, Features, Plugins, Telemetry, etc.)
│   ├── core/                  # Application engine, pipeline, service providers & helpers
│   ├── container/             # DI container with autowiring, singletons, scoped & circular detection
│   ├── config/                # Dot-notation typed configuration & Env parser
│   ├── console/               # CLI console bus, styling, table renderer & 35+ commands
│   ├── http/                  # Normalized Request, Response, JsonResponse & Kernel
│   ├── routing/               # Fluent Router, route groups, named routes, parameter constraints
│   ├── middleware/            # Security headers, CSRF token guard, rate limiter, CORS
│   ├── validation/            # Input validator & typed FormRequest
│   ├── database/              # Multi-driver PDO connection manager & transaction manager
│   ├── query-builder/         # Fluent Query Builder with SQLite/MySQL/PostgreSQL dialects
│   ├── orm/                   # Active Record / Data Mapper hybrid models & relationships
│   ├── migrations/            # Schema builder, blueprints, migration runner & rollbacks
│   ├── auth/                  # Authentication manager, guards & password hashing
│   ├── authorization/         # Gate & RBAC policy system (deny-by-default)
│   ├── security/              # AEAD Encrypter, SecurityAuditor, CSRF manager, SSRF validator
│   ├── workflow/              # Workflow engine, step orchestrator & saga compensations
│   ├── multitenancy/          # Tenant manager, resolution strategies & isolation middleware
│   ├── feature-flags/         # Dynamic feature manager, percentage rollouts & closures
│   ├── plugin-manager/        # Plugin manager, filesystem discovery & lifecycle
│   ├── devtools/              # OpenTelemetry-compatible Tracer & CorrelationMiddleware
│   ├── admin/                 # Admin Studio telemetry reporting & metrics
│   ├── view/                  # Server-rendered layouts, sections & accessible UI components
│   ├── frontend/              # Vite integration, manifest reader, asset resolver & preloads
│   ├── frontend-react/        # Optional React client bootstrapping & mount adapter
│   ├── frontend-vue/          # Optional Vue client bootstrapping & mount adapter
│   ├── cache/                 # Cache manager (array, file, database stores & atomic locks)
│   ├── queue/                 # Queue manager, sync & database drivers, worker daemon
│   ├── events/                # Event dispatcher & subscribers
│   ├── scheduler/             # Cron task scheduler with overlap prevention
│   ├── mail/                  # Mailer abstraction, Mailables & test fakes
│   ├── notifications/         # Multi-channel notification subsystem
│   ├── storage/               # Filesystem abstraction with traversal defense
│   ├── ai/                    # AI Chat, Streaming, Embeddings, RAG, Tools & Safety
│   ├── logging/               # Structured logger with secret redactor & correlation IDs
│   ├── monitoring/            # QueryObserver, query budgets & N+1 detection engine
│   └── testing/               # TestCase, TestResponse, TestRunner & assertions
├── skeleton/                  # Reference application starter
│   ├── app/                   # Controllers, Use cases, Domain models & Infrastructure
│   ├── bootstrap/app.php      # Application bootstrapping & provider registration
│   ├── config/                # Environment-specific configuration files
│   └── public/index.php       # HTTP web entrypoint
├── docs/adr/                  # Architecture Decision Records (ADRs 0001 - 0010)
└── tests/                     # 91 automated tests passing with zero dependencies
```

---

## 🛠️ Complete CLI Command Reference

Run commands using `php cna <command>`:

| Command | Namespace | Description |
|---|---|---|
| `app:info` | App | Display platform, environment, and ecosystem architecture status |
| `health:check` | Health | Perform operational health check on runtime, memory, DB, and cache |
| `env:check` | Env | Verify environment variables against `.env.example` definitions |
| `config:show` | Config | Display configuration values with sensitive data masked |
| `db:backup` | Database | Create a database backup snapshot |
| `db:restore` | Database | Restore a database snapshot from backup file |
| `make:module` | Make | Scaffold a new modular architecture domain (Controllers, Models, Services, Routes) |
| `make:plugin` | Make | Scaffold a new modular plugin package (plugin.php, Provider, manifest) |
| `workflow:list` | Workflow | List all registered workflow definitions |
| `workflow:run` | Workflow | Execute a registered workflow with optional context payload |
| `workflow:inspect`| Workflow | Inspect workflow steps, states, conditions, and compensation logic |
| `security:audit` | Security | Perform deep security audit across dependencies, headers, and permissions |
| `security:dependencies` | Security | Audit Composer dependencies, constraints, and lockfile integrity |
| `security:headers` | Security | Audit HTTP security headers against OWASP ASVS recommendations |
| `security:permissions` | Security | Audit filesystem permissions on sensitive paths and directories |
| `security:report` | Security | Generate full security audit report in structured JSON or text format |
| `security:check` | Security | Audit project configuration against OWASP ASVS baseline security rules |
| `frontend:install`| Frontend | Scaffold baseline Vite configuration, package.json, and asset dirs |
| `frontend:dev` | Frontend | Inspect and guide the Vite development server on loopback |
| `frontend:build` | Frontend | Verify production Vite build artifacts and manifest |
| `frontend:status`| Frontend | Report frontend engine status, Vite reachability, and manifest |
| `frontend:clear` | Frontend | Clear cached frontend manifest and preloaded asset metadata |
| `make:ui-module` | Make | Scaffold a new frontend UI feature module with TypeScript and styles |
| `make:component` | Make | Generate a server-rendered PHP UI component or view template |
| `make:controller`| Make | Scaffold a thin transport controller |
| `make:service` | Make | Scaffold an application use case service |
| `make:repository`| Make | Scaffold a domain repository interface and SQL implementation |
| `make:model` | Make | Scaffold an ORM model class |
| `make:migration` | Make | Create a new versioned database migration |
| `migrate` | Database | Execute pending database migrations |
| `migrate:rollback`| Database | Rollback the last migration batch |
| `migrate:refresh`| Database | Rollback all migrations and run them again |
| `db:status` | Database | Show status and batch of each database migration |
| `db:profile` | Database | Display query logs and detect potential N+1 query patterns |
| `db:seed` | Database | Seed the database with records |
| `cache:clear` | Cache | Flush the application cache |
| `queue:work` | Queue | Start the background queue worker daemon |
| `queue:failed` | Queue | List failed queue jobs |
| `queue:retry` | Queue | Retry a failed job by ID or retry `all` |
| `queue:flush` | Queue | Flush all failed queue jobs |
| `schedule:run` | Scheduler | Run scheduled cron tasks |
| `ai:usage` | AI | Report token consumption and tenant quota usage |
| `ai:providers` | AI | List configured AI providers and model allowlist |
| `ai:diagnose` | AI | Run diagnostic connectivity & PromptGuard injection checks |
| `doctor` | General | Verify runtime health, extensions, storage writability, and advisories |
| `serve` | General | Start the local development web server with URL rewriting |
| `test` | General | Run the automated test suite (**91 passing tests**) |
| `benchmark` | General | Run framework micro-benchmarks for DI, routing, and ORM |

---

## 🧪 Testing & Validation

Run the framework test suite:
```bash
php cna test
```
**91 automated tests** pass with zero external runtime dependencies.

Run the skeleton architecture tests:
```bash
php cna test skeleton/tests
```
**41 skeleton tests** pass.

Run micro-benchmarks:
```bash
php cna benchmark
```
- **Container Resolution**: ~400,000 ops/second (~2.5 µs/op)
- **Router Match**: ~40,000 ops/second (~25 µs/op)
- **QueryBuilder Compilation**: ~47,000 ops/second (~21 µs/op)
- **Model Hydration**: ~650,000 ops/second (~1.5 µs/op)

---

## 📜 License

The CnA Framework is open-sourced software licensed under the [MIT license](LICENSE).
Designed and engineered by **Alan Shaju** (2026).

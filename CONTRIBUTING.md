# Contributing to CnA Framework

Thank you for considering contributing to the **CnA Framework**! We welcome contributions, bug reports, feature suggestions, and documentation improvements.

---

## 🛠️ Development Setup

1. **Prerequisites**:
   - PHP 8.4 or higher
   - PDO extension with SQLite, MySQL, or PostgreSQL drivers
   - OpenSSL and MBString extensions

2. **Clone the Repository**:
   ```bash
   git clone https://github.com/Alan2k2/CnA-Framework.git
   cd CnA-Framework
   ```

3. **Initialize Environment**:
   ```bash
   cp .env.example .env
   php cna key:generate
   ```

4. **Verify Health**:
   ```bash
   php cna doctor
   php cna security:check
   ```

---

## 🧪 Running Tests & Quality Checks

Before submitting a Pull Request, all tests and benchmarks must pass:

```bash
# Run complete test suite (Unit, Feature, Architecture)
php cna test

# Run micro-benchmarks
php cna benchmark
```

---

## 📐 Architecture Guidelines

The CnA Framework enforces strict architectural rules defined in `CnA.pdf`:
1. **Decoupled Packages**: Code within `packages/*` must remain decoupled with explicit contracts in `packages/contracts`.
2. **Layered Architecture**: Transport Controllers $\rightarrow$ Application Services $\rightarrow$ Domain Repositories $\rightarrow$ Infrastructure / SQL Adapters.
3. **N+1 Prevention**: Never use unmonitored lazy loading in application or framework code. Use $O(1)$ eager loading via `Model::with()`.
4. **OWASP ASVS Baseline**: Maintain AES-256-GCM encryption, strict parameter bindings, constant-time comparisons, and strict SSRF defenses.

---

## 🏛️ Architecture Decision Records (ADRs)

If your change proposes a significant design shift or introduces a new paradigm, please author an ADR in `docs/adr/` following the existing numbered convention (`docs/adr/0009-...md`).

---

## 📜 Code of Conduct

Please treat everyone with respect and kindness. Let's build high-quality, secure software together!


# Security Policy & Vulnerability Reporting

The CnA Framework team takes the security of our framework, users, and the wider PHP ecosystem seriously. We appreciate the efforts of the security research community in helping us maintain the highest security standards.

---

## 🛡️ Supported Versions

Only the latest major and minor releases receive active security updates.

| Version | Supported | Status |
| :--- | :--- | :--- |
| **2.1.x** | :white_check_mark: Yes | **Current Active Version** (Security & Feature Updates) |
| **2.0.x** | :white_check_mark: Yes | Security Patches Only |
| **1.0.x** | :x: No | End of Life (EOL) |
| **< 1.0** | :x: No | End of Life (EOL) |

---

## 🚨 Reporting a Vulnerability

If you discover a security vulnerability within CnA Framework, **please do NOT report it via public GitHub issues, discussions, or social media**.

Instead, please submit a responsible disclosure report directly to the security maintainer:

- **Security Contact**: **Alan Shaju** (Lead Framework Architect)
- **Email**: [alanshaju26@icloud.com](mailto:alanshaju26@icloud.com)
- **Subject Line**: `[SECURITY VULNERABILITY] CnA Framework - <Component Name>`

### What to Include in Your Report:
To help us triage and patch the vulnerability quickly, please provide:
1. **Component affected** (e.g. `packages/security`, `packages/http`, `packages/database`, etc.)
2. **Type of vulnerability** (e.g. Remote Code Execution, SQL Injection, SSRF, Path Traversal, Authentication Bypass)
3. **Step-by-step reproduction instructions** or a minimal Proof of Concept (PoC) script.
4. **Impact assessment**: What an attacker could achieve by exploiting this flaw.
5. **Proposed fix / mitigation** (if available).

---

## ⏱️ Response Timelines & SLA

We commit to the following response timeline for all legitimate security reports:

| Milestone | Target Response Time |
| :--- | :--- |
| **Initial Acknowledgment** | Within **24–48 hours** |
| **Triage & Severity Assessment** | Within **3 business days** |
| **Patch Development & Testing** | Within **7 business days** (for Critical / High severity) |
| **Public Release & Advisory** | Coordinated release date agreed upon with the reporter |

---

## 🤝 Coordinated Disclosure & Credit

- We strictly adhere to **Coordinated Vulnerability Disclosure (CVD)** principles.
- Once a fix is verified and ready for release, we will release a patch version (e.g. `v2.1.1`) along with a GitHub Security Advisory (GHSA).
- Reporters will be credited in the release notes and GitHub Security Advisory (unless they wish to remain anonymous).
- Please allow us reasonable time to resolve the issue before disclosing it publicly.

---

## 🔒 Built-in Security Architecture

CnA Framework is built with native **OWASP ASVS Level 2** baseline defenses:
- **AEAD Encryption**: Authenticated AES-256-GCM with key rotation and integrity tag verification.
- **SSRF Defense**: Zero-trust IP validator blocking internal, loopback, link-local, and cloud metadata ranges (169.254.169.254, 127.0.0.0/8, 10.0.0.0/8, 192.168.0.0/16, ::1).
- **Password Hashing**: Argon2id with memory cost 65536 and time cost 4 (Bcrypt fallback).
- **SQL Injection Prevention**: Prepared statement parameter binding with dialect-aware identifier escaping across SQLite, MySQL, and PostgreSQL.
- **Constant-Time Verification**: `hash_equals` used across all authentication tokens, CSRF tokens, and signatures.
- **Automated Security Auditing**: Built-in CLI command `php cna security:audit` verifying dependencies, headers, and file permissions.

Thank you for helping keep CnA Framework and its community safe!

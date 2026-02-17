# TransactiWar — Battle for Security

**CS6903: Network Security, 2025-26**
Department of Computer Science and Engineering, IIT Hyderabad

---

## Overview

TransactiWar is a secure web application built with pure PHP and PostgreSQL. It implements user authentication, profile management, money transfers, and comprehensive activity logging — all without using any security frameworks or ORMs.

## Quick Start (Docker)

### Prerequisites
- Docker and Docker Compose installed

### Run the Application

```bash
# Build and start all services
docker-compose up --build

# The application will be available at:
# http://localhost:8080
```

### Stop the Application

```bash
docker-compose down

# To also remove database data:
docker-compose down -v
```

## Test Accounts

The following test accounts are created automatically on startup:

| Username | Email                        | Password       | Balance |
|----------|------------------------------|----------------|---------|
| alice    | alice@transactiwar.local     | Test@12345678  | Rs. 100 |
| bob      | bob@transactiwar.local       | Test@12345678  | Rs. 100 |
| charlie  | charlie@transactiwar.local   | Test@12345678  | Rs. 100 |
| dave     | dave@transactiwar.local      | Test@12345678  | Rs. 100 |
| eve      | eve@transactiwar.local       | Test@12345678  | Rs. 100 |

You can also create additional accounts via the create_accounts.php script:
```bash
docker-compose exec web php /var/www/create_accounts.php
```

## Technology Stack

| Component  | Technology        |
|------------|-------------------|
| Frontend   | HTML, CSS, JS, Tailwind CSS (CDN) |
| Backend    | PHP 8.2 (pure, no frameworks) |
| Database   | PostgreSQL 15     |
| Web Server | Apache 2          |
| Container  | Docker + Docker Compose |

## Database Schema

### Tables

1. **users** — User accounts with balance constraint (`CHECK balance >= 0`)
2. **transactions** — Money transfer records with self-transfer constraint
3. **activity_logs** — All user activity (page, username, timestamp, IP)
4. **attack_logs** — Security events (CSRF violations, brute force, path traversal, etc.)
5. **failed_logins** — Failed login attempt tracking for brute force protection
6. **rate_limits** — Rate limiting tracking per identifier/action

### Key Constraints
- `users.balance >= 0` — Prevents negative balance at DB level
- `transactions.sender_id != receiver_id` — Prevents self-transfers at DB level
- Unique constraints on `users.username` and `users.email`
- Foreign keys with `ON DELETE CASCADE/SET NULL` as appropriate

## Project Structure

```
├── app/
│   ├── config/
│   │   ├── database.php        # PDO singleton with prepared statements
│   │   └── security.php        # Security constants and configuration
│   ├── controllers/
│   │   ├── AuthController.php  # Registration, login, logout
│   │   ├── DashboardController.php
│   │   ├── ProfileController.php  # Profile CRUD + image upload
│   │   ├── SearchController.php   # User search
│   │   └── TransferController.php # Money transfers + history
│   ├── middleware/
│   │   ├── auth.php            # Session management + validation
│   │   ├── csrf.php            # CSRF token generation + validation
│   │   ├── logging.php         # Activity + attack logging
│   │   ├── rate_limit.php      # Rate limiting + account lockout
│   │   ├── security_headers.php # Security HTTP headers
│   │   └── validation.php      # Input validation (whitelist approach)
│   ├── models/
│   │   ├── User.php            # User DB operations
│   │   └── Transaction.php     # Transfer with FOR UPDATE locking
│   ├── public/
│   │   ├── .htaccess           # URL rewriting
│   │   └── index.php           # Single entry point (router)
│   └── views/
│       ├── layout.php          # Base layout template
│       ├── login.php / register.php
│       ├── dashboard.php
│       ├── profile.php / edit_profile.php / view_profile.php
│       ├── search.php
│       ├── transfer.php / transactions.php
├── docker/
│   ├── init.sql                # DB schema
│   ├── seed.sql                # Test account data
│   ├── create_accounts.php     # Account creation script
│   ├── entrypoint.sh           # Container startup script
│   ├── php.ini                 # Hardened PHP config
│   └── apache.conf             # Hardened Apache config
├── uploads/                    # Mounted volume (outside web root)
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## Security Mechanisms Implemented

### 1. SQL Injection Prevention
- **All** database queries use PDO prepared statements with parameterized queries
- `PDO::ATTR_EMULATE_PREPARES = false` forces real server-side prepared statements
- No string concatenation in any SQL query

### 2. XSS Prevention
- All output escaped with `htmlspecialchars(ENT_QUOTES | ENT_HTML5, 'UTF-8')`
- Content-Security-Policy header restricts script sources
- Input length validation on all fields

### 3. CSRF Protection
- Per-session CSRF tokens generated with `random_bytes(32)`
- Validated on every POST request using constant-time `hash_equals()`
- Tokens regenerate after expiry (1 hour)

### 4. Session Security
- `session_regenerate_id(true)` on login to prevent session fixation
- HttpOnly cookies (`session.cookie_httponly = 1`)
- SameSite=Strict cookie attribute
- Session timeout after 30 minutes of inactivity
- User-agent hash validation to detect session hijacking
- Custom session name (`TW_SESSID`)

### 5. Password Security
- BCrypt hashing with cost factor 12 (`password_hash()` / `password_verify()`)
- Strong password policy: min 10 chars, uppercase, lowercase, digit, special char
- Constant-time password comparison

### 6. Brute Force Protection
- Failed login tracking in database
- Account lockout after 5 failed attempts (15-minute cooldown)
- IP-based rate limiting
- Dummy password hash on non-existent usernames (prevents user enumeration via timing)

### 7. File Upload Security
- MIME type validation using `finfo_file()` (server-side, not client-reported)
- Extension whitelist: jpg, jpeg, png only
- File size limit: 2MB
- `getimagesize()` verification
- Files renamed with `random_bytes(16)` to prevent path traversal
- Stored in `/var/uploads/` (outside web root)
- Served through PHP proxy endpoint (no direct file access)
- Old images deleted on replacement

### 8. Money Transfer Security
- PostgreSQL transactions with `BEGIN`/`COMMIT`/`ROLLBACK`
- `SELECT ... FOR UPDATE` row-level locking prevents race conditions
- Consistent lock ordering (by user ID) prevents deadlocks
- `WHERE balance >= amount` in UPDATE prevents negative balance
- DB-level CHECK constraint as additional safety net
- Self-transfer prevented at both application and DB level

### 9. IDOR Prevention
- Session-based user identification (never trust user-supplied user_id for ownership)
- Transfer comments visible only to receiver
- Ownership verification before showing private data

### 10. Security Headers
- `X-Frame-Options: DENY` — Prevents clickjacking
- `X-Content-Type-Options: nosniff` — Prevents MIME sniffing
- `Content-Security-Policy` — Restricts resource loading
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` — Disables camera, microphone, geolocation
- `Cache-Control: no-store` — Prevents caching of sensitive data
- Server signature removed

### 11. Logging
- All user activity logged: page accessed, username, timestamp, client IP
- Security events logged to `attack_logs` table (CSRF violations, brute force, path traversal, suspicious uploads)
- Failed login attempts tracked separately
- Error details logged server-side only (never exposed to users)

### 12. Additional Hardening
- Apache configured with `Options -Indexes -FollowSymLinks`
- Non-public directories explicitly denied in Apache config
- Single entry point architecture (all requests through `index.php`)
- PHP dangerous functions disabled (`exec`, `system`, `passthru`, etc.)
- `expose_php = Off` — Hides PHP version
- `display_errors = Off` — No error details exposed
- Generic error messages to users

## Assumptions

1. Application runs over HTTP in Docker (HTTPS would be configured at reverse proxy level for deployment)
2. All test accounts start with Rs. 100 balance as specified
3. Transfer comments are only visible to the receiver (not the sender) as a privacy measure
4. Session timeout is set to 30 minutes of inactivity
5. Account lockout duration is 15 minutes after 5 failed login attempts

## References

- PHP Manual: https://www.php.net/manual/
- OWASP Web Security Testing Guide: https://owasp.org/www-project-web-security-testing-guide/
- OWASP Cheat Sheet Series: https://cheatsheetseries.owasp.org/
- PostgreSQL Documentation: https://www.postgresql.org/docs/15/
- Tailwind CSS: https://tailwindcss.com/
- Docker Documentation: https://docs.docker.com/

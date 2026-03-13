# TransactiWar — Battle for Security

**CS6903: Network Security, 2025-26**
Department of Computer Science and Engineering, IIT Hyderabad

## Team Members

| # | Name | Roll No | Email |
|---|------|---------|-------|
| 1 | Mayank Malhotra | CS25MTECH11016 | cs25mtech11016@iith.ac.in |
| 2 | Mohammed Abdul Momin Siddiqui | CS25MTECH11017 | cs25mtech11017@iith.ac.in |
| 3 | Sajid Ali | CS25MTECH11019 | cs25mtech11019@iith.ac.in |
| 4 | Pokale Jay Atul | CS25MTECH11013 | cs25mtech11013@iith.ac.in |
| 5 | Khwaja Abdul Samad | CS25MTECH11014 | cs25mtech11014@iith.ac.in |

---

## Overview

TransactiWar is a secure web application built with pure PHP and PostgreSQL. It implements user authentication, profile management, money transfers, and comprehensive activity logging — all without using any security frameworks or ORMs.

---

## Option 1: Quick Start with Docker (Recommended for Submission)

### Prerequisites
- Docker and Docker Compose installed ([Download Docker](https://www.docker.com/products/docker-desktop/))

### Run

```bash
git clone <your-repo-url>
cd TransactiWar

# Build and start everything (database + app)
docker compose up --build

# App will be at: https://localhost:8443
# (HTTP on http://localhost:8080 redirects automatically to HTTPS)
# Note: Your browser will show a self-signed certificate warning — click "Advanced" and proceed.
```

### Stop

```bash
docker compose down

# To also delete all database data:
docker compose down -v
```

That's it — Docker handles everything automatically (database, tables, test accounts).

---

## Option 2: Local Development Setup (Without Docker)

### Step 1: Install Prerequisites (macOS)

Install Homebrew (if not installed):
```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

Install PHP and PostgreSQL:
```bash
brew install php@8.2
brew install postgresql@15
```

Add them to your PATH — run this once:
```bash
echo 'export PATH="/opt/homebrew/opt/php@8.2/bin:/opt/homebrew/opt/postgresql@15/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

Verify installation:
```bash
php -v          # Should show PHP 8.2.x
psql --version  # Should show psql (PostgreSQL) 15.x
```

### Step 1 (Alternative): Install Prerequisites (Ubuntu/Debian Linux)

```bash
sudo apt update
sudo apt install php8.2 php8.2-pgsql php8.2-mbstring php8.2-zip postgresql postgresql-contrib
```

### Step 1 (Alternative): Install Prerequisites (Windows)

1. Download PHP 8.2 from https://windows.php.net/download/ (Thread Safe version)
2. Extract to `C:\php` and add to System PATH
3. Enable extensions in `php.ini`: uncomment `extension=pdo_pgsql` and `extension=pgsql`
4. Download PostgreSQL from https://www.postgresql.org/download/windows/

### Step 2: Start PostgreSQL

**macOS:**
```bash
brew services start postgresql@15
```

**Linux:**
```bash
sudo systemctl start postgresql
```

**Windows:**
PostgreSQL starts automatically as a service after installation.

### Step 3: Create Database and User

Open the PostgreSQL shell:
```bash
psql postgres
```

> **Note:** On Linux, you may need: `sudo -u postgres psql`

Run these SQL commands:
```sql
CREATE DATABASE transactiwar;
CREATE USER twuser WITH PASSWORD 'tw_s3cur3_p@ss!';
GRANT ALL PRIVILEGES ON DATABASE transactiwar TO twuser;
\c transactiwar
GRANT ALL ON SCHEMA public TO twuser;
\q
```

### Step 4: Load Database Schema

```bash
psql -U twuser -d transactiwar -f docker/init.sql
```

> If it asks for a password, enter: `tw_s3cur3_p@ss!`

### Step 5: Update Config for Local Use

Edit `app/config/database.php` — change line 12:
```php
// Change this:
$host = getenv('DB_HOST') ?: 'db';

// To this:
$host = getenv('DB_HOST') ?: 'localhost';
```

Edit `app/config/security.php` — change line 20:
```php
// Change this:
define('UPLOAD_DIR', '/var/uploads/');

// To this:
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
```

### Step 6: Create Test Accounts

```bash
DB_HOST=localhost DB_PORT=5432 DB_NAME=transactiwar DB_USER=twuser DB_PASS='tw_s3cur3_p@ss!' php docker/create_accounts.php
```

You should see:
```
Created account: alice
Created account: bob
...
All test accounts created successfully!
Password for all accounts: Test@12345678
```

### Step 7: Run the Application

```bash
DB_HOST=localhost DB_PORT=5432 DB_NAME=transactiwar DB_USER=twuser DB_PASS='tw_s3cur3_p@ss!' php -S localhost:8080 -t app/public/
```

Open your browser: **http://localhost:8080**

### Step 8 (Optional): Install pgAdmin for GUI Database Access

Download from https://www.pgadmin.org/download/ and connect with:
- Host: `localhost`
- Port: `5432`
- Database: `transactiwar`
- Username: `twuser`
- Password: `tw_s3cur3_p@ss!`

---

## Test Accounts

These accounts are created automatically (both Docker and local):

| Username | Email                        | Password       | Balance |
|----------|------------------------------|----------------|---------|
| alice    | alice@transactiwar.local     |Test@12345678   | Rs. 100 |
| bob      | bob@transactiwar.local       | Test@12345678  | Rs. 100 |
| charlie  | charlie@transactiwar.local   | Test@12345678  | Rs. 100 |
| dave     | dave@transactiwar.local      | Test@12345678  | Rs. 100 |
| eve      | eve@transactiwar.local       | Test@12345678  | Rs. 100 |

You can also register new accounts from the app.

---

## Technology Stack

| Component  | Technology        |
|------------|-------------------|
| Frontend   | HTML, CSS, JS, Tailwind CSS (CDN) |
| Backend    | PHP 8.2 (pure, no frameworks) |
| Database   | PostgreSQL 15     |
| Web Server | Apache 2 (Docker) / PHP built-in server (local) |
| Container  | Docker + Docker Compose |

---

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

---

## Project Structure

```
├── app/
│   ├── config/
│   │   ├── database.php          # PDO singleton with prepared statements
│   │   └── security.php          # Security constants and configuration
│   ├── controllers/
│   │   ├── AuthController.php    # Registration, login, logout
│   │   ├── DashboardController.php
│   │   ├── ProfileController.php # Profile CRUD + image upload
│   │   ├── SearchController.php  # User search
│   │   └── TransferController.php # Money transfers + history
│   ├── middleware/
│   │   ├── auth.php              # Session management + validation
│   │   ├── csrf.php              # CSRF token generation + validation
│   │   ├── logging.php           # Activity + attack logging
│   │   ├── rate_limit.php        # Rate limiting + account lockout
│   │   ├── security_headers.php  # Security HTTP headers
│   │   └── validation.php        # Input validation (whitelist approach)
│   ├── models/
│   │   ├── User.php              # User DB operations
│   │   └── Transaction.php       # Transfer with FOR UPDATE locking
│   ├── public/
│   │   ├── .htaccess             # URL rewriting (Apache)
│   │   └── index.php             # Single entry point (router)
│   └── views/
│       ├── layout.php            # Base layout template
│       ├── login.php / register.php
│       ├── dashboard.php
│       ├── profile.php / edit_profile.php / view_profile.php
│       ├── search.php
│       └── transfer.php / transactions.php
├── docker/
│   ├── init.sql                  # DB schema (tables, indexes, constraints)
│   ├── seed.sql                  # Placeholder (accounts created by PHP script)
│   ├── create_accounts.php       # Script to auto-create test accounts
│   ├── entrypoint.sh             # Docker container startup script
│   ├── php.ini                   # Hardened PHP config
│   └── apache.conf               # Hardened Apache config
├── Dockerfile
├── docker-compose.yml
└── README.md
```

---

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
- Tokens rotated after every successful validation (single-use)
- Tokens regenerate after expiry (1 hour)
- Logout requires POST with CSRF token (prevents forced logout via `<img src="/logout">`)

### 4. Session Security
- `session_regenerate_id(true)` on login to prevent session fixation
- HttpOnly cookies (`session.cookie_httponly = 1`)
- Secure cookie flag (`session.cookie_secure = 1`) — cookies only sent over HTTPS
- SameSite=Strict cookie attribute
- Session timeout after 30 minutes of inactivity
- User-agent hash validation to detect session hijacking
- IP address binding — session invalidated if client IP changes
- Custom session name (`TW_SESSID`)

### 5. Password Security
- BCrypt hashing with cost factor 12 (`password_hash()` / `password_verify()`)
- Strong password policy: min 10 chars, uppercase, lowercase, digit, special char
- Constant-time password comparison

### 6. Brute Force Protection
- Failed login tracking in database
- Account lockout after 5 failed attempts (15-minute cooldown)
- IP-based rate limiting with atomic DB operations (prevents race condition bypass)
- Rate limiter fails closed on DB error (blocks requests rather than allowing bypass)
- Rate limiting on search endpoint to prevent username enumeration
- Dummy password hash on non-existent usernames (prevents user enumeration via timing)

### 7. File Upload Security
- MIME type validation using `finfo_file()` (server-side, not client-reported)
- Extension whitelist: jpg, jpeg, png only
- File size limit: 2MB
- `getimagesize()` verification
- Files renamed with `random_bytes(16)` to prevent path traversal
- Stored outside web root (`/var/uploads/` in Docker, `uploads/` locally)
- Served through PHP proxy endpoint (no direct file access)
- Images re-encoded via GD library to strip metadata and embedded payloads
- Old images deleted on replacement (with regex-validated filename)

### 8. Money Transfer Security
- PostgreSQL transactions with `BEGIN`/`COMMIT`/`ROLLBACK`
- `SELECT ... FOR UPDATE` row-level locking prevents race conditions
- Consistent lock ordering (by user ID) prevents deadlocks
- `WHERE balance >= amount` in UPDATE prevents negative balance
- DB-level CHECK constraint as additional safety net
- Self-transfer prevented at both application and DB level
- String-based amount formatting (`number_format`) to avoid float precision issues

### 9. IDOR Prevention
- Session-based user identification (never trust user-supplied user_id for ownership)
- Transfer comments visible to both sender and receiver
- Ownership verification before showing private data

### 10. HTTPS / TLS
- Self-signed TLS certificate generated at build time via OpenSSL (RSA 2048-bit, 365-day validity)
- HTTP (port 80) redirects to HTTPS (port 443) via Apache 301 redirect
- Only TLS 1.2 and TLS 1.3 allowed — SSLv3, TLS 1.0, TLS 1.1 explicitly disabled
- Strong cipher suites enforced (ECDHE with AES-GCM)

### 11. Security Headers
- `Strict-Transport-Security` — HSTS forces HTTPS for 2 years (includeSubDomains)
- `X-Frame-Options: DENY` — Prevents clickjacking
- `X-Content-Type-Options: nosniff` — Prevents MIME sniffing
- `Content-Security-Policy` — Restricts resource loading (`unsafe-inline` removed from `script-src`)
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` — Disables camera, microphone, geolocation
- `Cache-Control: no-store` — Prevents caching of sensitive data
- Server signature removed

### 12. Logging
- All user activity logged to `activity_logs` table: page accessed, username, timestamp, client IP
- Security events logged to `attack_logs` table (CSRF violations, brute force, path traversal, suspicious uploads)
- Failed login attempts tracked in `failed_logins` table
- Error details logged server-side only (never exposed to users)

### 13. Additional Hardening
- Apache configured with `Options -Indexes +FollowSymLinks`
- Non-public directories explicitly denied in Apache config
- Single entry point architecture (all requests through `index.php`)
- PHP dangerous functions disabled (`exec`, `system`, `passthru`, etc.)
- `expose_php = Off` — Hides PHP version
- `display_errors = Off` — No error details exposed
- Generic error messages to users

---

## Assumptions

1. Application runs over HTTPS in Docker using a self-signed certificate (port 443). HTTP (port 80) redirects automatically to HTTPS.
2. All test accounts start with Rs. 100 balance as specified
3. Transfer comments are visible to both sender and receiver
4. Session timeout is set to 30 minutes of inactivity
5. Account lockout duration is 15 minutes after 5 failed login attempts

---

## Troubleshooting

**"psql: command not found"**
Add PostgreSQL to your PATH:
```bash
export PATH="/opt/homebrew/opt/postgresql@15/bin:$PATH"
```

**"php: command not found"**
Add PHP to your PATH:
```bash
export PATH="/opt/homebrew/opt/php@8.2/bin:$PATH"
```

**"Failed to listen on localhost:8080 (Address already in use)"**
Kill the existing process:
```bash
lsof -ti:8080 | xargs kill -9
```

**"FATAL: role 'twuser' does not exist"**
Create the user first — see Step 3 in Local Setup.

**"FATAL: Peer authentication failed"**
On Linux, edit `/etc/postgresql/15/main/pg_hba.conf`, change `peer` to `md5` for local connections, then restart PostgreSQL.

---

## References

- PHP Manual: https://www.php.net/manual/
- OWASP Web Security Testing Guide: https://owasp.org/www-project-web-security-testing-guide/
- OWASP Cheat Sheet Series: https://cheatsheetseries.owasp.org/
- PostgreSQL Documentation: https://www.postgresql.org/docs/15/
- Tailwind CSS: https://tailwindcss.com/
- Docker Documentation: https://docs.docker.com/

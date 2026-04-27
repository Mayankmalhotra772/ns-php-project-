# Project Structure — TransactiWar

```
ns-php-project/
├── Dockerfile                        # Docker image build instructions
├── docker-compose.yml                # Container orchestration config
├── README.md                         # Project documentation
├── Transactiwar.pdf                  # Project report
│
├── docker/                           # Docker & server configuration
│   ├── apache.conf                   # Apache virtual host, HTTPS, HSTS config
│   ├── php.ini                       # PHP security settings (session, uploads)
│   ├── entrypoint.sh                 # Container startup script
│   ├── init.sql                      # Database schema (tables, indexes)
│   ├── seed.sql                      # Initial data seeding
│   └── create_accounts.php           # Script to create competition accounts
│
├── uploads/                          # User uploaded profile images
│   └── *.jpeg / *.png
│
└── app/                              # Main application code
    │
    ├── public/                       # Web root (only publicly accessible folder)
    │   ├── index.php                 # Application entry point
    │   ├── router.php                # URL routing — maps URLs to controllers
    │   └── assets/
    │       └── tailwind.min.js       # Tailwind CSS (local, no CDN)
    │
    ├── config/                       # Application configuration
    │   ├── database.php              # Database connection (singleton PDO)
    │   └── security.php             # Security constants (session timeout, rate limits)
    │
    ├── controllers/                  # Request handlers — business logic
    │   ├── AuthController.php        # Register, login, logout
    │   ├── DashboardController.php   # Dashboard — balance, recent transactions
    │   ├── TransferController.php    # Send money, transaction history
    │   ├── SearchController.php      # Search users by username or ID
    │   └── ProfileController.php    # View/edit profile, upload image, change password
    │
    ├── middleware/                   # Runs before every request
    │   ├── auth.php                  # Session validation, IP/UA checks, session management
    │   ├── csrf.php                  # CSRF token generation and validation
    │   ├── rate_limit.php            # IP-based rate limiting and account lockout
    │   ├── security_headers.php      # HTTP security headers (CSP, HSTS, X-Frame etc.)
    │   ├── validation.php            # Input validation (username, password, amount etc.)
    │   └── logging.php               # Activity logging and attack event logging
    │
    ├── models/                       # Database interaction layer
    │   ├── User.php                  # User CRUD — find, create, verify password, balance
    │   └── Transaction.php           # Transfer execution, history, balance checks
    │
    └── views/                        # HTML templates (rendered by controllers)
        ├── layout.php                # Shared HTML layout (nav, head)
        ├── login.php                 # Login form
        ├── register.php              # Registration form
        ├── dashboard.php             # Dashboard page
        ├── transfer.php              # Transfer form
        ├── transactions.php          # Transaction history table
        ├── search.php                # User search page
        ├── profile.php               # Own profile view
        ├── edit_profile.php          # Profile edit form
        └── view_profile.php          # Public profile view of another user
```

## Request Flow

```
Browser Request
      ↓
public/index.php         → bootstraps app, loads config and middleware
      ↓
public/router.php        → matches URL to controller method
      ↓
middleware/              → security_headers → csrf → auth → rate_limit
      ↓
controllers/             → handles request, calls models
      ↓
models/                  → queries database, returns data
      ↓
views/                   → renders HTML response
      ↓
Browser Response
```

## Security Layers

| Layer | File | Protection |
|-------|------|------------|
| HTTPS enforcement | `docker/apache.conf` | HTTP → HTTPS redirect |
| HSTS | `docker/apache.conf` | 2-year strict transport security |
| Secure cookies | `docker/php.ini` | HttpOnly, Secure, SameSite=Strict |
| CSRF | `middleware/csrf.php` | Token per session, rotated after each use |
| Rate limiting | `middleware/rate_limit.php` | IP-based, atomic DB operations |
| Session protection | `middleware/auth.php` | IP + User-Agent binding |
| Input validation | `middleware/validation.php` | All user input sanitized |
| Security headers | `middleware/security_headers.php` | CSP, X-Frame, Referrer-Policy |
| Attack logging | `middleware/logging.php` | All suspicious events logged |

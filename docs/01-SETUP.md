# Setup Guide

Complete guide for setting up the GitRoast development environment.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Initial Setup](#initial-setup)
3. [Environment Configuration](#environment-configuration)
4. [Database Setup](#database-setup)
5. [Third-Party Services](#third-party-services)
6. [Development Servers](#development-servers)
7. [Verification](#verification)

---

## Prerequisites

### Required Software

| Software | Version | Purpose |
|----------|---------|---------|
| PHP | 8.3+ | Runtime |
| Composer | 2.x | PHP dependency management |
| Node.js | 20+ | Frontend build tools |
| npm/pnpm | Latest | JavaScript dependencies |
| MySQL | 8.0+ | Database (or PostgreSQL 15+) |
| Redis | 7.0+ | Cache and queues |
| Git | 2.x | Version control |

### PHP Extensions Required

```bash
# Check installed extensions
php -m

# Required extensions:
- bcmath
- ctype
- curl
- dom
- fileinfo
- json
- mbstring
- openssl
- pcre
- pdo
- pdo_mysql (or pdo_pgsql)
- redis
- tokenizer
- xml
- zip
```

### Install PHP Extensions (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install php8.3-{bcmath,curl,dom,mbstring,mysql,redis,xml,zip}
```

---

## Initial Setup

### Step 1: Create Laravel Project

```bash
# Option A: New project
composer create-project laravel/laravel gitroast
cd gitroast

# Option B: Clone existing repository
git clone <repository-url>
cd gitroast
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Install JavaScript Dependencies

```bash
npm install
# or
pnpm install
```

### Step 4: Environment File

```bash
cp .env.example .env
php artisan key:generate
```

---

## Environment Configuration

### Complete `.env` File Template

```env
# ===========================================
# APPLICATION
# ===========================================
APP_NAME="GitRoast"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

# ===========================================
# DATABASE
# ===========================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gitroast
DB_USERNAME=root
DB_PASSWORD=

# ===========================================
# CACHE & SESSION
# ===========================================
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# ===========================================
# QUEUE
# ===========================================
QUEUE_CONNECTION=redis

# ===========================================
# REDIS
# ===========================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ===========================================
# GITHUB API
# ===========================================
GITHUB_TOKEN=your_personal_access_token
GITHUB_API_VERSION=2022-11-28
GITHUB_RATE_LIMIT_BUFFER=100

# ===========================================
# AI ANALYSIS (Claude)
# ===========================================
ANTHROPIC_API_KEY=your_anthropic_api_key
ANTHROPIC_MODEL=claude-sonnet-4-20250514
ANTHROPIC_MAX_TOKENS=4096

# ===========================================
# STRIPE PAYMENTS
# ===========================================
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
STRIPE_PRICE_FULL_REPORT=price_xxx

# ===========================================
# ANALYSIS SETTINGS
# ===========================================
ANALYSIS_CACHE_HOURS=24
ANALYSIS_MAX_REPOS=30
ANALYSIS_FREE_ISSUES_LIMIT=3

# ===========================================
# RATE LIMITING
# ===========================================
RATE_LIMIT_ANALYSIS_PER_IP=10
RATE_LIMIT_ANALYSIS_WINDOW=60

# ===========================================
# FILAMENT ADMIN
# ===========================================
FILAMENT_FILESYSTEM_DISK=public

# ===========================================
# OPENAPI / SWAGGER
# ===========================================
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_CONST_HOST=http://localhost:8000/api

# ===========================================
# LOGGING
# ===========================================
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
```

---

## Database Setup

### Step 1: Create Database

```bash
# MySQL
mysql -u root -p -e "CREATE DATABASE gitroast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# PostgreSQL
createdb gitroast
```

### Step 2: Run Migrations

```bash
php artisan migrate
```

### Step 3: Seed Initial Data (Optional)

```bash
php artisan db:seed
```

---

## Third-Party Services

### GitHub Personal Access Token

1. Go to https://github.com/settings/tokens
2. Click "Generate new token (classic)"
3. Select scopes:
   - `public_repo` (read public repositories)
   - `read:user` (read user profile data)
4. Copy token to `.env` as `GITHUB_TOKEN`

### Anthropic Claude API Key

1. Go to https://console.anthropic.com/
2. Create account and verify
3. Go to API Keys section
4. Generate new API key
5. Copy to `.env` as `ANTHROPIC_API_KEY`

### Stripe Setup

1. Create account at https://stripe.com
2. Go to Developers → API Keys
3. Copy test keys to `.env`:
   - `STRIPE_KEY` (Publishable key)
   - `STRIPE_SECRET` (Secret key)
4. Create product and price:
   ```bash
   # Use Stripe Dashboard or CLI
   stripe products create --name="Full GitRoast Report"
   stripe prices create --product=prod_xxx --unit-amount=900 --currency=usd
   ```
5. Set up webhook endpoint:
   - URL: `https://yourdomain.com/api/webhook/stripe`
   - Events: `checkout.session.completed`, `payment_intent.succeeded`

---

## Development Servers

### Start All Services

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server (for frontend assets)
npm run dev

# Terminal 3: Queue worker
php artisan queue:work --tries=3 --backoff=30

# Terminal 4: (Optional) Laravel scheduler
php artisan schedule:work
```

### Using Laravel Sail (Docker Alternative)

```bash
# Install Sail
composer require laravel/sail --dev
php artisan sail:install

# Start containers
./vendor/bin/sail up -d

# Run commands inside container
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev
```

---

## Verification

### Step 1: Check Application Health

```bash
# Application boots correctly
php artisan about

# Routes are registered
php artisan route:list --path=api

# Config is cached properly
php artisan config:show app
```

### Step 2: Test Database Connection

```bash
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit
```

### Step 3: Test Redis Connection

```bash
php artisan tinker
>>> Cache::store('redis')->put('test', 'hello', 60);
>>> Cache::store('redis')->get('test');
>>> exit
```

### Step 4: Access Admin Panel

```bash
# Create admin user
php artisan make:filament-user

# Visit http://localhost:8000/admin
```

### Step 5: Access API Documentation

```bash
# Generate OpenAPI docs
php artisan l5-swagger:generate

# Visit http://localhost:8000/api/documentation
```

### Step 6: Test GitHub API Connection

```bash
php artisan tinker
>>> app(\App\Services\GitHub\GitHubService::class)->getUser('torvalds');
>>> exit
```

---

## Common Issues

### Issue: Redis Connection Refused

```bash
# Check Redis is running
redis-cli ping

# Start Redis (Ubuntu)
sudo systemctl start redis-server
```

### Issue: GitHub API Rate Limit

- Ensure `GITHUB_TOKEN` is set
- Authenticated requests: 5,000/hour
- Unauthenticated: 60/hour

### Issue: Swagger UI Not Loading

```bash
# Publish assets
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"

# Clear cache
php artisan l5-swagger:generate
php artisan cache:clear
```

### Issue: Filament Assets Not Loading

```bash
# Publish Filament assets
php artisan filament:assets

# Clear view cache
php artisan view:clear
```

---

## Next Steps

1. Read [Packages Documentation](02-PACKAGES.md) for all dependencies
2. Review [Architecture Guide](03-ARCHITECTURE.md) for code structure
3. Set up [Database Schema](04-DATABASE.md)
4. Configure [API Routes](05-API.md)
5. Build [Admin Panel](06-FILAMENT.md)

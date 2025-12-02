# Deployment Documentation

Complete guide for deploying GitRoast to production.

---

## Table of Contents

1. [Server Requirements](#server-requirements)
2. [Environment Setup](#environment-setup)
3. [Deployment Options](#deployment-options)
4. [Manual Deployment](#manual-deployment)
5. [Laravel Forge Deployment](#laravel-forge-deployment)
6. [Docker Deployment](#docker-deployment)
7. [Post-Deployment](#post-deployment)
8. [Monitoring & Maintenance](#monitoring--maintenance)

---

## Server Requirements

### Minimum Specifications

| Resource | Minimum | Recommended |
|----------|---------|-------------|
| CPU | 2 cores | 4 cores |
| RAM | 4 GB | 8 GB |
| Storage | 40 GB SSD | 80 GB SSD |
| OS | Ubuntu 22.04+ | Ubuntu 24.04 |

### Software Requirements

| Software | Version |
|----------|---------|
| PHP | 8.3+ |
| MySQL | 8.0+ (or PostgreSQL 15+) |
| Redis | 7.0+ |
| Nginx | 1.24+ |
| Node.js | 20+ |
| Composer | 2.x |
| Supervisor | 4.x |

---

## Environment Setup

### Production Environment Variables

```env
# ===========================================
# APPLICATION
# ===========================================
APP_NAME="GitRoast"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://gitroast.dev

# ===========================================
# OPTIMIZATION
# ===========================================
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file

# ===========================================
# DATABASE
# ===========================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gitroast_production
DB_USERNAME=gitroast
DB_PASSWORD=secure-database-password

# ===========================================
# CACHE & SESSION
# ===========================================
CACHE_STORE=redis
CACHE_PREFIX=gitroast_cache

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=.gitroast.dev

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
# LOGGING
# ===========================================
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

# ===========================================
# EXTERNAL SERVICES
# ===========================================
GITHUB_TOKEN=ghp_your_production_token
GITHUB_API_VERSION=2022-11-28

ANTHROPIC_API_KEY=sk-ant-your-production-key
ANTHROPIC_MODEL=claude-sonnet-4-20250514
ANTHROPIC_MAX_TOKENS=4096

STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
STRIPE_PRICE_FULL_REPORT=price_xxx

# ===========================================
# RATE LIMITING
# ===========================================
RATE_LIMIT_ANALYSIS_PER_IP=10
RATE_LIMIT_ANALYSIS_WINDOW=60

# ===========================================
# FILAMENT
# ===========================================
FILAMENT_FILESYSTEM_DISK=public

# ===========================================
# HORIZON
# ===========================================
HORIZON_DOMAIN=gitroast.dev
HORIZON_PATH=horizon

# ===========================================
# TELESCOPE (Disabled in production)
# ===========================================
TELESCOPE_ENABLED=false
```

---

## Deployment Options

### Option 1: Manual VPS Deployment

Best for: Full control, cost-effective

### Option 2: Laravel Forge

Best for: Managed servers, easy SSL, automatic deployments

### Option 3: Docker/Kubernetes

Best for: Scalability, containerized environments

---

## Manual Deployment

### Step 1: Server Preparation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y \
    nginx \
    mysql-server \
    redis-server \
    supervisor \
    git \
    unzip \
    curl

# Install PHP 8.3
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
    php8.3-fpm \
    php8.3-cli \
    php8.3-mysql \
    php8.3-redis \
    php8.3-curl \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-gd \
    php8.3-intl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### Step 2: Configure MySQL

```bash
# Secure MySQL
sudo mysql_secure_installation

# Create database and user
sudo mysql -e "CREATE DATABASE gitroast_production;"
sudo mysql -e "CREATE USER 'gitroast'@'localhost' IDENTIFIED BY 'secure-password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON gitroast_production.* TO 'gitroast'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### Step 3: Configure Nginx

**/etc/nginx/sites-available/gitroast:**

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name gitroast.dev www.gitroast.dev;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name gitroast.dev www.gitroast.dev;

    root /var/www/gitroast/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/gitroast.dev/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/gitroast.dev/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/xml;

    # Logging
    access_log /var/log/nginx/gitroast.access.log;
    error_log /var/log/nginx/gitroast.error.log;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Static assets caching
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny hidden files
    location ~ /\. {
        deny all;
    }

    # Deny vendor directory
    location ~ ^/vendor/ {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/gitroast /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 4: SSL Certificate

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Get certificate
sudo certbot --nginx -d gitroast.dev -d www.gitroast.dev

# Auto-renewal test
sudo certbot renew --dry-run
```

### Step 5: Deploy Application

```bash
# Create application directory
sudo mkdir -p /var/www/gitroast
sudo chown -R $USER:www-data /var/www/gitroast

# Clone repository
cd /var/www
git clone git@github.com:yourusername/gitroast.git gitroast
cd gitroast

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Setup environment
cp .env.example .env
# Edit .env with production values
nano .env

# Generate key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
php artisan filament:cache-components

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Step 6: Configure Supervisor

**/etc/supervisor/conf.d/gitroast-horizon.conf:**

```ini
[program:gitroast-horizon]
process_name=%(program_name)s
command=php /var/www/gitroast/artisan horizon
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/gitroast/storage/logs/horizon.log
stopwaitsecs=3600
```

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start gitroast-horizon
```

### Step 7: Configure Cron

```bash
# Edit crontab
sudo crontab -e -u www-data

# Add Laravel scheduler
* * * * * cd /var/www/gitroast && php artisan schedule:run >> /dev/null 2>&1
```

---

## Laravel Forge Deployment

### Step 1: Create Server

1. Sign up at [forge.laravel.com](https://forge.laravel.com)
2. Connect your server provider (DigitalOcean, AWS, etc.)
3. Create server with:
   - PHP 8.3
   - MySQL 8.0
   - Redis

### Step 2: Create Site

1. Add new site: `gitroast.dev`
2. Enable SSL with Let's Encrypt
3. Configure environment variables

### Step 3: Configure Deploy Script

```bash
cd /home/forge/gitroast.dev

git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --optimize-autoloader

npm ci
npm run build

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan view:cache
$FORGE_PHP artisan icons:cache
$FORGE_PHP artisan filament:cache-components
$FORGE_PHP artisan horizon:terminate
```

### Step 4: Configure Daemon

- Command: `php artisan horizon`
- User: `forge`
- Directory: `/home/forge/gitroast.dev`

---

## Docker Deployment

### Dockerfile

```dockerfile
FROM php:8.3-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    nodejs \
    npm \
    mysql-client \
    redis \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    bcmath \
    gd \
    intl \
    pcntl

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm ci && npm run build

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Expose port
EXPOSE 80

# Start services
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

### docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: gitroast-app
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - .:/var/www/html
      - ./storage:/var/www/html/storage
    environment:
      - APP_ENV=production
    depends_on:
      - mysql
      - redis
    networks:
      - gitroast

  mysql:
    image: mysql:8.0
    container_name: gitroast-mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - gitroast

  redis:
    image: redis:7-alpine
    container_name: gitroast-redis
    restart: unless-stopped
    volumes:
      - redis_data:/data
    networks:
      - gitroast

  horizon:
    build: .
    container_name: gitroast-horizon
    restart: unless-stopped
    command: php artisan horizon
    volumes:
      - .:/var/www/html
    depends_on:
      - mysql
      - redis
    networks:
      - gitroast

volumes:
  mysql_data:
  redis_data:

networks:
  gitroast:
    driver: bridge
```

---

## Post-Deployment

### Deployment Checklist

- [ ] Environment variables configured
- [ ] SSL certificate active
- [ ] Database migrated
- [ ] Cache cleared and rebuilt
- [ ] Queue workers running (Horizon)
- [ ] Scheduler cron running
- [ ] Admin user created
- [ ] Stripe webhook configured
- [ ] Error monitoring configured (Sentry)
- [ ] Backups configured

### Create Admin User

```bash
php artisan make:filament-user
```

### Configure Stripe Webhook

1. Go to Stripe Dashboard → Webhooks
2. Add endpoint: `https://gitroast.dev/api/webhook/stripe`
3. Select events:
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
4. Copy signing secret to `.env`

### Health Check

```bash
# Check application
curl -s https://gitroast.dev/api/health | jq

# Check queues
php artisan queue:monitor default,high,low

# Check Horizon
php artisan horizon:status
```

---

## Monitoring & Maintenance

### Log Rotation

**/etc/logrotate.d/gitroast:**

```
/var/www/gitroast/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        /usr/lib/php/php8.3-fpm-checkconf || exit 0
        /usr/bin/systemctl reload php8.3-fpm
    endscript
}
```

### Backup Script

```bash
#!/bin/bash
# /usr/local/bin/gitroast-backup.sh

DATE=$(date +%Y-%m-%d_%H-%M-%S)
BACKUP_DIR="/var/backups/gitroast"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u gitroast -p'password' gitroast_production | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Files backup (storage only)
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz -C /var/www/gitroast storage

# Cleanup old backups (keep 7 days)
find $BACKUP_DIR -type f -mtime +7 -delete
```

### Monitoring with Laravel Telescope (Dev Only)

```bash
# Install (dev only)
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### Error Tracking with Sentry

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=your-dsn
```

### Uptime Monitoring

Configure external monitoring:
- [UptimeRobot](https://uptimerobot.com)
- [Pingdom](https://pingdom.com)
- [StatusCake](https://statuscake.com)

Monitor endpoints:
- `https://gitroast.dev/api/health`
- `https://gitroast.dev/admin`

### Performance Optimization

```bash
# OPcache (production php.ini)
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=64
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0

# PHP-FPM tuning
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
```

### Deployment Script

```bash
#!/bin/bash
# /var/www/gitroast/deploy.sh

set -e

echo "🚀 Starting deployment..."

cd /var/www/gitroast

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Build assets
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
php artisan filament:cache-components

# Restart queue workers
php artisan horizon:terminate

# Restart PHP-FPM
sudo systemctl reload php8.3-fpm

echo "✅ Deployment complete!"
```

---

## Quick Commands Reference

```bash
# Deploy
./deploy.sh

# Clear all caches
php artisan optimize:clear

# Check logs
tail -f storage/logs/laravel.log

# Queue status
php artisan horizon:status

# Failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Database backup
mysqldump -u gitroast -p gitroast_production > backup.sql

# Maintenance mode
php artisan down --secret="bypass-token"
php artisan up
```

---

## Congratulations! 🎉

You now have complete documentation for the GitRoast project. All documentation files are available in the `docs/` directory.

# GitRoast - Blockers and Known Issues

This document lists blockers and items that need attention before production deployment.

## High Priority Blockers

### 1. Environment Configuration Required

Before the application can run, you must configure:

```env
# Required - GitHub API
GITHUB_TOKEN=                    # GitHub Personal Access Token

# Required - Claude AI
ANTHROPIC_API_KEY=               # Anthropic API key

# Required - Paddle Payments
PADDLE_API_KEY=                  # Paddle API key
PADDLE_WEBHOOK_SECRET=           # Paddle webhook secret
PADDLE_PRICE_FULL_REPORT=        # Paddle Price ID for full report
PADDLE_SANDBOX=true              # Set to false for production
```

### 2. Paddle Product Setup

Create a product and price in Paddle Dashboard:
1. Go to Paddle Dashboard > Catalog > Products
2. Create product "GitRoast Full Report"
3. Create a one-time price (e.g., $9.99)
4. Copy the price ID to `PADDLE_PRICE_FULL_REPORT`
5. Set up webhook endpoint at `https://yourdomain.com/api/webhooks/paddle`
6. Subscribe to `transaction.completed` and `transaction.payment_failed` events

### 3. Database Setup

```bash
php artisan migrate
```

### 4. Create Admin User

```bash
php artisan tinker
>>> User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => Hash::make('your-password'),
    'is_admin' => true
])
```

### 5. Queue Worker

For analysis processing, a queue worker must be running:
```bash
php artisan queue:work
```

For production, use Supervisor or similar process manager.

## Medium Priority Issues

### 1. Larastan Level

Currently configured at level 8. To reach level 10:
- Fix nullsafe operator issues in resources
- Add proper generic types to factories
- Fix `when()` method parameter types in resources

See `phpstan.neon` for currently ignored errors.

### 2. Redis Configuration

For production, configure Redis for:
- Queue driver: `QUEUE_CONNECTION=redis`
- Cache driver: `CACHE_STORE=redis`

### 3. Rate Limiting

Current rate limit is 10 requests/hour per IP. Adjust in:
- `app/Http/Controllers/Api/AnalysisController.php`

### 4. GitHub API Rate Limits

Without authentication: 60 requests/hour
With authentication: 5,000 requests/hour

Ensure `GITHUB_TOKEN` is set for production.

## Low Priority / Nice to Have

### 1. Missing Features (Future)

- Email notifications when analysis completes
- Webhook notifications
- Re-analysis functionality
- Historical analysis tracking per username
- API authentication (API keys)
- Admin notifications for failed jobs

### 2. Testing Setup

Tests are configured to use MySQL. Before running tests:
```bash
# Create test database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS gitroast_backend_test;"

# Run tests
./vendor/bin/pest
```

If using SQLite instead, update `phpunit.xml`:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```
And ensure `php-sqlite3` extension is installed.

### 3. Testing Coverage

Additional tests needed:
- Payment webhook tests
- GitHub service mocking tests
- AI analysis service mocking tests
- End-to-end tests

### 4. Monitoring

Consider adding:
- Laravel Horizon for queue monitoring
- Exception tracking (Sentry, Bugsnag)
- Application metrics

### 5. Security

Before production:
- Review CORS settings
- Add API throttling middleware
- Consider API authentication for endpoints
- Review webhook signature verification

## Type Issues (Larastan)

The following type issues are currently ignored in `phpstan.neon`:

1. **Resource `when()` method** - Laravel Resources have complex conditional logic
2. **Nullsafe operators** - Using `?->` on non-nullable types (defensive coding)
3. **Paddle types** - Paddle SDK entities have dynamic properties
4. **Generic factory types** - HasFactory trait requires generic specification

To fix these for level 10:
```php
// Instead of
$this->created_at?->toIso8601String()

// Use
$this->created_at->toIso8601String()
```

## Frontend Integration

The backend expects a frontend at `APP_FRONTEND_URL` for:
- Paddle success/cancel redirects
- Analysis result pages

Update `.env` with your frontend URL.

## Production Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure all required environment variables
- [ ] Run migrations
- [ ] Create admin user
- [ ] Set up queue worker (Supervisor)
- [ ] Configure Redis
- [ ] Set up Paddle webhook endpoint
- [ ] Set `PADDLE_SANDBOX=false` for production
- [ ] Configure HTTPS
- [ ] Set up monitoring
- [ ] Review rate limits

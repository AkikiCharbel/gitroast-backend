# Packages Documentation

Complete list of all packages required for the GitRoast project.

---

## Table of Contents

1. [Composer Packages](#composer-packages)
2. [NPM Packages](#npm-packages)
3. [Package Installation Commands](#package-installation-commands)
4. [Package Configuration](#package-configuration)

---

## Composer Packages

### Core Framework

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/framework` | ^11.0 | Core Laravel framework |
| `laravel/sanctum` | ^4.0 | API authentication |
| `laravel/horizon` | ^5.0 | Queue monitoring dashboard |
| `laravel/telescope` | ^5.0 | Debug assistant (dev only) |

### Admin Panel

| Package | Version | Purpose |
|---------|---------|---------|
| `filament/filament` | ^3.2 | Admin panel framework |
| `filament/forms` | ^3.2 | Form builder |
| `filament/tables` | ^3.2 | Table builder |
| `filament/notifications` | ^3.2 | Toast notifications |
| `filament/widgets` | ^3.2 | Dashboard widgets |
| `filament/infolists` | ^3.2 | Information display |

### API Documentation

| Package | Version | Purpose |
|---------|---------|---------|
| `darkaonline/l5-swagger` | ^8.6 | OpenAPI/Swagger documentation |

### HTTP & External APIs

| Package | Version | Purpose |
|---------|---------|---------|
| `guzzlehttp/guzzle` | ^7.8 | HTTP client for external APIs |
| `saloonphp/saloon` | ^3.0 | Elegant API integration library |
| `saloonphp/laravel-plugin` | ^3.0 | Laravel integration for Saloon |
| `saloonphp/rate-limit-plugin` | ^2.0 | API rate limiting for Saloon |
| `saloonphp/cache-plugin` | ^3.0 | Response caching for Saloon |

### Payments

| Package | Version | Purpose |
|---------|---------|---------|
| `stripe/stripe-php` | ^13.0 | Stripe SDK |
| `laravel/cashier` | ^15.0 | Stripe integration (optional alternative) |

### Data & Validation

| Package | Version | Purpose |
|---------|---------|---------|
| `spatie/laravel-data` | ^4.0 | Elegant DTOs |
| `spatie/laravel-query-builder` | ^5.0 | API query building |
| `spatie/laravel-json-api-paginate` | ^1.0 | JSON API pagination |

### Utilities

| Package | Version | Purpose |
|---------|---------|---------|
| `spatie/laravel-ray` | ^1.0 | Debugging (dev only) |
| `spatie/laravel-activitylog` | ^4.0 | Activity logging |
| `spatie/laravel-settings` | ^3.0 | Application settings |
| `spatie/laravel-enum` | ^3.0 | Enum support |

### Testing (Dev)

| Package | Version | Purpose |
|---------|---------|---------|
| `pestphp/pest` | ^2.0 | Testing framework |
| `pestphp/pest-plugin-laravel` | ^2.0 | Laravel Pest plugin |
| `pestphp/pest-plugin-faker` | ^2.0 | Faker for Pest |
| `mockery/mockery` | ^1.0 | Mocking library |
| `fakerphp/faker` | ^1.0 | Fake data generator |

### Code Quality (Dev)

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/pint` | ^1.0 | Code style fixer |
| `larastan/larastan` | ^2.0 | Static analysis |
| `nunomaduro/collision` | ^8.0 | Error reporting |

---

## NPM Packages

### Build Tools

| Package | Version | Purpose |
|---------|---------|---------|
| `vite` | ^5.0 | Build tool |
| `laravel-vite-plugin` | ^1.0 | Laravel Vite integration |

### CSS & Styling

| Package | Version | Purpose |
|---------|---------|---------|
| `tailwindcss` | ^3.4 | Utility CSS framework |
| `@tailwindcss/forms` | ^0.5 | Form styling plugin |
| `@tailwindcss/typography` | ^0.5 | Typography plugin |
| `autoprefixer` | ^10.0 | CSS vendor prefixes |
| `postcss` | ^8.0 | CSS processing |

### JavaScript

| Package | Version | Purpose |
|---------|---------|---------|
| `axios` | ^1.6 | HTTP client |
| `alpinejs` | ^3.0 | Lightweight JS framework |

### TypeScript (Optional)

| Package | Version | Purpose |
|---------|---------|---------|
| `typescript` | ^5.0 | TypeScript compiler |
| `@types/node` | ^20.0 | Node.js type definitions |

---

## Package Installation Commands

### One-Line Installation (Composer)

```bash
# Core packages
composer require \
    filament/filament:"^3.2" \
    darkaonline/l5-swagger:"^8.6" \
    saloonphp/saloon:"^3.0" \
    saloonphp/laravel-plugin:"^3.0" \
    saloonphp/rate-limit-plugin:"^2.0" \
    saloonphp/cache-plugin:"^3.0" \
    stripe/stripe-php:"^13.0" \
    spatie/laravel-data:"^4.0" \
    spatie/laravel-query-builder:"^5.0" \
    spatie/laravel-activitylog:"^4.0" \
    spatie/laravel-settings:"^3.0" \
    laravel/horizon:"^5.0"

# Dev packages
composer require --dev \
    pestphp/pest:"^2.0" \
    pestphp/pest-plugin-laravel:"^2.0" \
    pestphp/pest-plugin-faker:"^2.0" \
    laravel/telescope:"^5.0" \
    larastan/larastan:"^2.0" \
    spatie/laravel-ray:"^1.0"
```

### One-Line Installation (NPM)

```bash
npm install -D \
    tailwindcss \
    @tailwindcss/forms \
    @tailwindcss/typography \
    autoprefixer \
    postcss \
    alpinejs \
    axios
```

---

## Package Configuration

### 1. Filament Setup

```bash
# Install Filament panels
php artisan filament:install --panels

# Publish config
php artisan vendor:publish --tag=filament-config

# Create admin user
php artisan make:filament-user
```

**config/filament.php** additions:
```php
return [
    'default_filesystem_disk' => env('FILAMENT_FILESYSTEM_DISK', 'public'),
    'auth' => [
        'guard' => env('FILAMENT_AUTH_GUARD', 'web'),
        'pages' => [
            'login' => \Filament\Pages\Auth\Login::class,
        ],
    ],
    'middleware' => [
        'auth' => [
            Authenticate::class,
        ],
        'base' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ],
    ],
];
```

### 2. L5-Swagger Setup

```bash
# Publish config and views
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

**config/l5-swagger.php**:
```php
return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'GitRoast API',
            ],
            'routes' => [
                'api' => 'api/documentation',
            ],
            'paths' => [
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
                'annotations' => [
                    base_path('app/Http/Controllers/Api'),
                    base_path('app/OpenApi'),
                ],
            ],
        ],
    ],
    'defaults' => [
        'routes' => [
            'docs' => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],
            'group_options' => [],
        ],
        'paths' => [
            'docs' => storage_path('api-docs'),
            'views' => base_path('resources/views/vendor/l5-swagger'),
            'base' => env('L5_SWAGGER_BASE_PATH', null),
            'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
            'excludes' => [],
        ],
        'scanOptions' => [
            'analyser' => null,
            'analysis' => null,
            'processors' => [],
            'pattern' => null,
            'exclude' => [],
            'open_api_spec_version' => env('L5_SWAGGER_OPEN_API_VERSION', \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION),
        ],
        'securityDefinitions' => [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                ],
            ],
            'security' => [
                ['bearerAuth' => []],
            ],
        ],
        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),
        'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', false),
        'proxy' => false,
        'additional_config_url' => null,
        'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', null),
        'validator_url' => null,
        'ui' => [
            'display' => [
                'dark_mode' => env('L5_SWAGGER_UI_DARK_MODE', false),
                'doc_expansion' => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
                'filter' => env('L5_SWAGGER_UI_FILTERS', true),
            ],
            'authorization' => [
                'persist_authorization' => env('L5_SWAGGER_UI_PERSIST_AUTHORIZATION', false),
                'oauth2' => [
                    'use_pkce_with_authorization_code_grant' => false,
                ],
            ],
        ],
        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://localhost:8000/api'),
        ],
    ],
];
```

### 3. Saloon Setup

```bash
# Publish config
php artisan vendor:publish --tag=saloon-config
```

**config/saloon.php**:
```php
return [
    'default_sender' => \Saloon\Http\Senders\GuzzleSender::class,
    'integrations' => [
        'cache' => [
            'driver' => 'redis',
            'ttl' => 3600,
        ],
    ],
];
```

### 4. Horizon Setup

```bash
# Publish config and assets
php artisan horizon:install
```

**config/horizon.php** (key sections):
```php
return [
    'domain' => env('HORIZON_DOMAIN'),
    'path' => 'horizon',
    'use' => 'default',
    'middleware' => ['web', 'auth'],
    
    'environments' => [
        'production' => [
            'supervisor-1' => [
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
        ],
        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
            ],
        ],
    ],
];
```

### 5. Pest Setup

```bash
# Initialize Pest
./vendor/bin/pest --init
```

**tests/Pest.php**:
```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Unit');
```

### 6. Spatie Laravel Data Setup

```bash
# Publish config
php artisan vendor:publish --tag=data-config
```

### 7. Activity Log Setup

```bash
# Publish migration and config
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan migrate
```

### 8. Tailwind Configuration

**tailwind.config.js**:
```javascript
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,ts,jsx,tsx}',
    ],
    theme: {
        extend: {
            colors: {
                // Score colors
                'score-excellent': '#22c55e',
                'score-good': '#84cc16',
                'score-average': '#eab308',
                'score-below': '#f97316',
                'score-poor': '#ef4444',
                // Brand colors
                'primary': '#6366f1',
                'background': '#0f172a',
                'surface': '#1e293b',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [forms, typography],
};
```

**postcss.config.js**:
```javascript
export default {
    plugins: {
        tailwindcss: {},
        autoprefixer: {},
    },
};
```

---

## Complete composer.json

```json
{
    "name": "gitroast/gitroast",
    "type": "project",
    "description": "AI-powered GitHub Profile Analyzer",
    "require": {
        "php": "^8.3",
        "darkaonline/l5-swagger": "^8.6",
        "filament/filament": "^3.2",
        "guzzlehttp/guzzle": "^7.8",
        "laravel/framework": "^11.0",
        "laravel/horizon": "^5.0",
        "laravel/sanctum": "^4.0",
        "laravel/tinker": "^2.9",
        "saloonphp/cache-plugin": "^3.0",
        "saloonphp/laravel-plugin": "^3.0",
        "saloonphp/rate-limit-plugin": "^2.0",
        "saloonphp/saloon": "^3.0",
        "spatie/laravel-activitylog": "^4.0",
        "spatie/laravel-data": "^4.0",
        "spatie/laravel-query-builder": "^5.0",
        "spatie/laravel-settings": "^3.0",
        "stripe/stripe-php": "^13.0"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "larastan/larastan": "^2.0",
        "laravel/pint": "^1.13",
        "laravel/sail": "^1.26",
        "laravel/telescope": "^5.0",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.0",
        "pestphp/pest": "^2.0",
        "pestphp/pest-plugin-faker": "^2.0",
        "pestphp/pest-plugin-laravel": "^2.0",
        "spatie/laravel-ignition": "^2.4",
        "spatie/laravel-ray": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi",
            "@php artisan filament:upgrade"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi",
            "@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\"",
            "@php artisan migrate --graceful --ansi"
        ],
        "test": "pest",
        "lint": "pint",
        "analyse": "phpstan analyse"
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "php-http/discovery": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

---

## Complete package.json

```json
{
    "name": "gitroast",
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build"
    },
    "devDependencies": {
        "@tailwindcss/forms": "^0.5.7",
        "@tailwindcss/typography": "^0.5.10",
        "alpinejs": "^3.13.5",
        "autoprefixer": "^10.4.17",
        "axios": "^1.6.7",
        "laravel-vite-plugin": "^1.0.2",
        "postcss": "^8.4.35",
        "tailwindcss": "^3.4.1",
        "vite": "^5.1.3"
    }
}
```

---

## Next Steps

1. Run the installation commands
2. Configure each package as shown above
3. Continue to [Architecture Documentation](03-ARCHITECTURE.md)

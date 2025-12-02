# GitRoast - GitHub Profile Analyzer

> AI-powered tool that analyzes GitHub profiles and tells developers exactly how their profile looks to recruiters, hiring managers, and potential collaborators.

## 🎯 One-Line Pitch

> "Find out what recruiters *actually* think when they see your GitHub — before they reject you."

---

## 📚 Documentation Index

| Document | Description |
|----------|-------------|
| [Setup Guide](docs/01-SETUP.md) | Initial project setup and environment configuration |
| [Packages](docs/02-PACKAGES.md) | Complete list of all required packages |
| [Architecture](docs/03-ARCHITECTURE.md) | System architecture and folder structure |
| [Database](docs/04-DATABASE.md) | Database schema, migrations, and models |
| [API Documentation](docs/05-API.md) | REST API endpoints and OpenAPI/Swagger setup |
| [Filament Admin](docs/06-FILAMENT.md) | Admin panel setup with Filament |
| [Services](docs/07-SERVICES.md) | Service layer documentation |
| [Queues & Jobs](docs/08-QUEUES.md) | Background job processing |
| [Testing](docs/09-TESTING.md) | Testing strategies and examples |
| [Deployment](docs/10-DEPLOYMENT.md) | Production deployment guide |

---

## 🚀 Quick Start

```bash
# 1. Clone and setup
git clone <repository-url>
cd gitroast
cp .env.example .env

# 2. Install dependencies
composer install
npm install

# 3. Generate keys and setup database
php artisan key:generate
php artisan migrate

# 4. Install Filament admin
php artisan filament:install --panels

# 5. Create admin user
php artisan make:filament-user

# 6. Generate OpenAPI documentation
php artisan l5-swagger:generate

# 7. Start development servers
php artisan serve
npm run dev
```

---

## 📁 Project Structure Overview

```
gitroast/
├── app/
│   ├── Actions/              # Single-purpose action classes
│   ├── Console/              # Artisan commands
│   ├── DTOs/                 # Data Transfer Objects
│   ├── Enums/                # PHP Enums
│   ├── Events/               # Event classes
│   ├── Exceptions/           # Custom exceptions
│   ├── Filament/             # Filament admin resources
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/          # API Controllers
│   │   ├── Middleware/
│   │   ├── Requests/         # Form Requests
│   │   └── Resources/        # API Resources
│   ├── Jobs/                 # Queue jobs
│   ├── Listeners/            # Event listeners
│   ├── Models/               # Eloquent models
│   ├── Policies/             # Authorization policies
│   ├── Providers/            # Service providers
│   └── Services/             # Business logic services
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docs/                     # Documentation
├── routes/
│   ├── api.php              # API routes
│   └── web.php              # Web routes
├── storage/
└── tests/
    ├── Feature/
    └── Unit/
```

---

## 🔑 Key Features

### Free Tier
- Overall score (0-100)
- 3 "deal breaker" issues shown
- Category scores (5 categories)
- Social share card with score

### Paid Tier ($9 one-time)
- Full detailed report
- All issues with explanations
- Specific fix recommendations
- Project-by-project analysis (top 6 repos)
- README quality scores
- Commit pattern analysis
- Improvement checklist

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | Laravel 11, PHP 8.3 |
| **Database** | MySQL 8 / PostgreSQL |
| **Cache/Queue** | Redis |
| **Admin Panel** | Filament 3 |
| **API Docs** | OpenAPI 3.0 (L5-Swagger) |
| **AI Analysis** | Claude API (Anthropic) |
| **Payments** | Stripe |
| **Testing** | Pest PHP |

---

## 📊 Analysis Categories

| Category | Weight | Description |
|----------|--------|-------------|
| Profile Completeness | 15% | Bio, avatar, location, website, README |
| Project Quality | 30% | Top repos: descriptions, READMEs, stars, activity |
| Contribution Consistency | 20% | Commit frequency, patterns, gaps |
| Technical Signals | 20% | Languages, diversity, modern stack |
| Community Engagement | 15% | PRs to others, issues, followers ratio |

---

## 🔗 Important Links

- **API Documentation**: `/api/documentation`
- **Admin Panel**: `/admin`
- **Health Check**: `/api/health`

---

## 📝 License

Proprietary - All rights reserved.

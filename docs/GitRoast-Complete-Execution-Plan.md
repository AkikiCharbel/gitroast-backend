# GitRoast — GitHub Profile Analyzer
## Complete Execution Plan: From Zero to Revenue

---

## Table of Contents

1. [Product Overview](#1-product-overview)
2. [Market & Positioning](#2-market--positioning)
3. [Feature Specification](#3-feature-specification)
4. [Monetization Strategy](#4-monetization-strategy)
5. [Technical Architecture](#5-technical-architecture)
6. [Database Schema](#6-database-schema)
7. [AI Analysis System](#7-ai-analysis-system)
8. [UI/UX Specification](#8-uiux-specification)
9. [Development Roadmap](#9-development-roadmap)
10. [Launch Strategy](#10-launch-strategy)
11. [Post-Launch Growth](#11-post-launch-growth)
12. [Prompts & AI Instructions](#12-prompts--ai-instructions)
13. [Risk Mitigation](#13-risk-mitigation)
14. [Success Metrics](#14-success-metrics)

---

## 1. Product Overview

### 1.1 What It Is

**GitRoast** is an AI-powered tool that analyzes GitHub profiles and tells developers exactly how their profile looks to recruiters, hiring managers, and potential collaborators.

### 1.2 One-Line Pitch

> "Find out what recruiters *actually* think when they see your GitHub — before they reject you."

### 1.3 Core Value Proposition

- **For job-seeking developers:** Know if your GitHub helps or hurts your applications
- **For freelancers:** Make your profile attract better clients
- **For open source contributors:** Understand how to increase visibility

### 1.4 Why This Works

| Factor | Advantage |
|--------|-----------|
| **Audience** | Developers — high intent, share tools, pay for career advancement |
| **Timing** | Tech layoffs = more developers job hunting |
| **Competition** | Minimal direct competitors (resume tools are saturated, GitHub analysis is not) |
| **Virality** | Scores are shareable ("I got 73/100, what did you get?") |
| **Your Skills** | You can build this fast with Laravel + API integration |

---

## 2. Market & Positioning

### 2.1 Target Audience (Priority Order)

**Primary: Job-Seeking Developers**
- Recently laid off or actively looking
- Want every advantage in interviews
- Already optimizing resumes, LinkedIn — GitHub is next
- Will pay $9-19 for actionable insights

**Secondary: Bootcamp Graduates / Junior Devs**
- Building portfolio from scratch
- Don't know what "good" looks like
- High motivation, limited budgets
- Good for free tier virality

**Tertiary: Freelance Developers**
- Use GitHub as portfolio for clients
- Higher willingness to pay
- Less volume, higher LTV

### 2.2 Competitive Landscape

| Competitor | What They Do | Gap |
|------------|--------------|-----|
| **Profile README generators** | Help create README.md | No analysis or scoring |
| **GitHub Stats widgets** | Show contribution stats | No interpretation or advice |
| **Resume analyzers** | Analyze resumes | Don't touch GitHub |
| **LinkedIn optimizers** | LinkedIn-focused | Ignore developer-specific signals |

**Your positioning:** The ONLY tool that tells developers how recruiters perceive their GitHub profile.

### 2.3 Messaging Framework

**Problem:** "Your GitHub is your developer resume. But you have no idea how it looks to recruiters."

**Agitation:** "You might have great code, but:
- Is your commit history consistent?
- Do your READMEs actually explain anything?
- Are you showcasing the right projects?
- Does your profile look active or dead?"

**Solution:** "GitRoast analyzes your profile the way a technical recruiter would — and tells you exactly what to fix."

---

## 3. Feature Specification

### 3.1 MVP Features (Week 1-2)

#### Free Tier
- Overall score (0-100)
- 3 "deal breaker" issues shown
- Category scores (5 categories, numbers only)
- Teaser of full report (blurred sections)
- Social share card with score

#### Paid Tier ($9 one-time)
- Full detailed report
- All issues with explanations
- Specific fix recommendations
- Project-by-project analysis (top 6 repos)
- README quality scores
- Commit pattern analysis
- Before/after improvement checklist

### 3.2 Analysis Categories

| Category | Weight | What It Measures |
|----------|--------|------------------|
| **Profile Completeness** | 15% | Bio, avatar, location, website, README |
| **Project Quality** | 30% | Top repos: descriptions, READMEs, stars, activity |
| **Contribution Consistency** | 20% | Commit frequency, patterns, gaps |
| **Technical Signals** | 20% | Languages used, diversity, modern stack |
| **Community Engagement** | 15% | PRs to others, issues, followers/following ratio |

### 3.3 Future Features (Post-MVP)

- **Role-specific analysis:** "Analyze for Frontend Developer role"
- **Comparison mode:** "Compare to average developer in your experience level"
- **Weekly monitoring:** Email alerts when score changes
- **Team plans:** Agencies checking candidate profiles
- **API access:** For bootcamps and recruiters

---

## 4. Monetization Strategy

### 4.1 Pricing Model

```
FREE TIER
├── Overall score
├── 3 critical issues
├── Category breakdown (scores only)
└── Shareable score card

FULL ROAST — $9 (one-time)
├── Everything in Free
├── Detailed analysis per category
├── Project-by-project breakdown
├── Specific fix recommendations
├── README quality analysis
├── Commit pattern insights
└── Improvement checklist

PRIORITY ROAST — $19 (one-time) [Future]
├── Everything in Full Roast
├── AI-generated improved bio
├── AI-generated project descriptions
├── "Fix it for me" suggestions
└── 30-day re-analysis included
```

### 4.2 Revenue Projections

**Conservative (Month 1-3):**
- 1,000 free analyses/month
- 5% conversion = 50 paid @ $9 = $450/month

**Moderate (Month 3-6):**
- 5,000 free analyses/month
- 7% conversion = 350 paid @ $9 = $3,150/month

**Optimistic (Month 6-12):**
- 15,000 free analyses/month
- 8% conversion = 1,200 paid @ $9 = $10,800/month

### 4.3 Unit Economics

| Metric | Value |
|--------|-------|
| Price | $9 |
| Stripe fees | $0.56 (2.9% + $0.30) |
| AI cost per analysis | ~$0.02-0.05 |
| GitHub API | Free |
| Hosting | ~$20/month fixed |
| **Net per sale** | ~$8.40 |

---

## 5. Technical Architecture

### 5.1 Recommended Stack

```
BACKEND
├── Laravel 11 (API + Queue processing)
├── MySQL/PostgreSQL (Database)
├── Redis (Caching + Queue)
└── Laravel Sanctum (API auth if needed)

FRONTEND
├── React + TypeScript (Main app)
├── TailwindCSS (Styling)
├── Vite (Build tool)
└── React Query (Data fetching)

INFRASTRUCTURE
├── Your existing server (or Forge/Vapor)
├── Cloudflare (CDN + protection)
└── GitHub API (Data source)

PAYMENTS
├── Stripe (via friend's account initially)
└── Migrate to own account after UK company

AI PROCESSING
├── Claude API (Primary analysis)
└── Fallback: OpenAI GPT-4
```

### 5.2 System Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER FLOW                                │
└─────────────────────────────────────────────────────────────────┘

[User enters GitHub username]
            │
            ▼
[Frontend validates input]
            │
            ▼
[API Request to Laravel backend]
            │
            ▼
[Check cache - analyzed in last 24h?]
      │              │
     YES            NO
      │              │
      ▼              ▼
[Return cached]  [Queue job for analysis]
                      │
                      ▼
            [Fetch GitHub data via API]
            - User profile
            - Top repositories (30)
            - Contribution data
            - README files
                      │
                      ▼
            [Send to Claude API for analysis]
                      │
                      ▼
            [Parse AI response → structured JSON]
                      │
                      ▼
            [Store in database]
                      │
                      ▼
            [Return results to frontend]
                      │
                      ▼
            [Show FREE tier results]
                      │
                      ▼
            [User clicks "Unlock Full Report"]
                      │
                      ▼
            [Stripe Checkout → $9]
                      │
                      ▼
            [Webhook confirms payment]
                      │
                      ▼
            [Unlock full report for this analysis]
```

### 5.3 API Endpoints

```php
// Public endpoints
POST   /api/analyze              // Start analysis (returns job ID)
GET    /api/analysis/{id}        // Get analysis results
GET    /api/analysis/{id}/status // Check job status

// Payment endpoints  
POST   /api/checkout/create      // Create Stripe session
POST   /api/webhook/stripe       // Handle Stripe webhooks

// Admin (Filament)
GET    /admin                    // Dashboard
GET    /admin/analyses           // View all analyses
GET    /admin/payments           // View payments
GET    /admin/stats              // Analytics
```

---

## 6. Database Schema

### 6.1 Core Tables

```sql
-- Analyses table
CREATE TABLE analyses (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) UNIQUE NOT NULL,
    github_username VARCHAR(255) NOT NULL,
    
    -- Status
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    
    -- Scores (0-100)
    overall_score TINYINT UNSIGNED NULL,
    profile_score TINYINT UNSIGNED NULL,
    projects_score TINYINT UNSIGNED NULL,
    consistency_score TINYINT UNSIGNED NULL,
    technical_score TINYINT UNSIGNED NULL,
    community_score TINYINT UNSIGNED NULL,
    
    -- Raw data (JSON)
    github_data JSON NULL,           -- Raw GitHub API response
    ai_analysis JSON NULL,           -- Full AI analysis
    
    -- Payment
    is_paid BOOLEAN DEFAULT FALSE,
    stripe_payment_id VARCHAR(255) NULL,
    paid_at TIMESTAMP NULL,
    
    -- Meta
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (github_username),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- Payments table
CREATE TABLE payments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    analysis_id BIGINT UNSIGNED NOT NULL,
    stripe_session_id VARCHAR(255) NOT NULL,
    stripe_payment_intent VARCHAR(255) NULL,
    amount_cents INT UNSIGNED NOT NULL,
    currency CHAR(3) DEFAULT 'USD',
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    customer_email VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (analysis_id) REFERENCES analyses(id),
    INDEX idx_session (stripe_session_id)
);

-- Rate limiting / abuse prevention
CREATE TABLE analysis_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    github_username VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_ip_time (ip_address, created_at),
    INDEX idx_username_time (github_username, created_at)
);
```

### 6.2 Laravel Models

```php
// app/Models/Analysis.php
class Analysis extends Model
{
    protected $casts = [
        'github_data' => 'array',
        'ai_analysis' => 'array',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];
    
    public function getFreeReportAttribute(): array
    {
        // Return limited data for free tier
    }
    
    public function getFullReportAttribute(): array
    {
        // Return complete analysis (only if paid)
    }
}
```

---

## 7. AI Analysis System

### 7.1 GitHub Data to Collect

```php
// Data points to fetch from GitHub API
$dataPoints = [
    // Profile
    'user' => [
        'name',
        'bio',
        'avatar_url',
        'location',
        'blog',        // website
        'company',
        'twitter_username',
        'public_repos',
        'followers',
        'following',
        'created_at',
    ],
    
    // Profile README (special repo: username/username)
    'profile_readme' => 'content if exists',
    
    // Repositories (top 30 by stars + recent activity)
    'repositories' => [
        'name',
        'description',
        'language',
        'stargazers_count',
        'forks_count',
        'open_issues_count',
        'created_at',
        'updated_at',
        'pushed_at',
        'has_readme',      // Check if README exists
        'readme_content',  // First 2000 chars
        'topics',
        'license',
        'is_fork',
    ],
    
    // Contribution data
    'contribution_calendar' => 'Last 12 months activity',
    
    // Languages across repos
    'languages' => 'aggregated from repos',
];
```

### 7.2 Main Analysis Prompt

```
SYSTEM PROMPT (for Claude):

You are a senior technical recruiter and engineering hiring manager with 15 years of experience reviewing developer profiles at top tech companies (Google, Meta, Stripe, etc.).

Your task is to analyze a GitHub profile and provide brutally honest, actionable feedback on how this profile appears to technical recruiters and hiring managers.

You must return a JSON response with the following structure:

{
  "overall_score": <0-100>,
  "summary": "<2-3 sentence overall assessment>",
  "first_impression": "<What a recruiter thinks in the first 5 seconds>",
  
  "categories": {
    "profile_completeness": {
      "score": <0-100>,
      "issues": ["<issue 1>", "<issue 2>"],
      "recommendations": ["<specific fix 1>", "<specific fix 2>"],
      "details": "<paragraph explanation>"
    },
    "project_quality": {
      "score": <0-100>,
      "issues": [],
      "recommendations": [],
      "details": ""
    },
    "contribution_consistency": {
      "score": <0-100>,
      "issues": [],
      "recommendations": [],
      "details": ""
    },
    "technical_signals": {
      "score": <0-100>,
      "issues": [],
      "recommendations": [],
      "details": ""
    },
    "community_engagement": {
      "score": <0-100>,
      "issues": [],
      "recommendations": [],
      "details": ""
    }
  },
  
  "deal_breakers": [
    {
      "issue": "<critical issue>",
      "why_it_matters": "<why recruiters care>",
      "fix": "<how to fix it>"
    }
  ],
  
  "top_projects_analysis": [
    {
      "repo_name": "<name>",
      "score": <0-100>,
      "strengths": ["<strength>"],
      "weaknesses": ["<weakness>"],
      "readme_quality": "<poor|basic|good|excellent>",
      "recommendations": ["<specific improvement>"]
    }
  ],
  
  "improvement_checklist": [
    {
      "priority": "<high|medium|low>",
      "task": "<specific action>",
      "time_estimate": "<e.g., 10 minutes>",
      "impact": "<what changes after doing this>"
    }
  ],
  
  "strengths": ["<genuine strength 1>", "<strength 2>"],
  
  "recruiter_perspective": "<What a recruiter would say about this profile in an internal meeting>"
}

SCORING GUIDELINES:

Overall Score Interpretation:
- 90-100: Exceptional. Top 5% of profiles. Ready for FAANG.
- 80-89: Strong. Would get interviews at most companies.
- 70-79: Good. Some clear improvements needed.
- 60-69: Average. Needs work to stand out.
- 50-59: Below average. Several red flags.
- Below 50: Significant issues. Needs major overhaul.

Category Scoring:

PROFILE COMPLETENESS (15% weight):
- Has professional avatar (not default): +15 points
- Has descriptive bio: +20 points
- Has location: +10 points
- Has website/blog: +15 points
- Has profile README: +25 points
- README is well-designed: +15 points
- Missing any = deduct proportionally

PROJECT QUALITY (30% weight):
- At least 3 non-forked repos with descriptions: baseline
- READMEs exist and are useful: +20 points
- Projects have stars from others: +15 points
- Projects are complete (not abandoned): +20 points
- Diverse project types: +10 points
- Projects demonstrate real skills: +25 points
- Has live demos/deployments: +10 points

CONTRIBUTION CONSISTENCY (20% weight):
- Regular commits (no huge gaps > 30 days): +40 points
- Active in last 30 days: +30 points
- Consistent pattern (not just sporadic bursts): +30 points

TECHNICAL SIGNALS (20% weight):
- Uses modern languages/frameworks: +25 points
- Multiple languages: +15 points
- Code quality visible in repos: +30 points
- Has tests in projects: +15 points
- Uses CI/CD (visible in repos): +15 points

COMMUNITY ENGAGEMENT (15% weight):
- PRs to other repos: +30 points
- Issues filed/discussed: +20 points
- Healthy follower/following ratio: +20 points
- Stars given to others: +15 points
- Contributions to open source: +15 points

CRITICAL RULES:
1. Be specific. Never say "improve your profile" — say exactly what to improve and how.
2. Be honest. Don't inflate scores to be nice. A bad profile is a bad profile.
3. Be actionable. Every issue must have a concrete fix.
4. Consider context. A student's profile is judged differently than a 10-year veteran.
5. Note red flags explicitly. Employers will see them.

DO NOT:
- Give scores above 85 unless the profile is genuinely exceptional
- Ignore obvious issues to be polite
- Give generic advice that applies to everyone
- Assume good intentions — judge what's visible

USER PROMPT:

Analyze this GitHub profile:

Username: {{username}}

Profile Data:
{{profile_json}}

Repositories (top by stars and recent activity):
{{repos_json}}

Profile README (if exists):
{{readme_content}}

Contribution Activity (last 12 months):
{{contribution_summary}}

Provide your analysis in the exact JSON format specified. Be brutally honest but constructive.
```

### 7.3 Scoring Algorithm (Backend Calculation)

```php
// app/Services/ScoreCalculator.php

class ScoreCalculator
{
    public function calculateOverallScore(array $aiAnalysis): int
    {
        $weights = [
            'profile_completeness' => 0.15,
            'project_quality' => 0.30,
            'contribution_consistency' => 0.20,
            'technical_signals' => 0.20,
            'community_engagement' => 0.15,
        ];
        
        $weightedSum = 0;
        foreach ($weights as $category => $weight) {
            $categoryScore = $aiAnalysis['categories'][$category]['score'] ?? 0;
            $weightedSum += $categoryScore * $weight;
        }
        
        return (int) round($weightedSum);
    }
}
```

---

## 8. UI/UX Specification

### 8.1 Pages

```
/                       → Landing page + input form
/analyze/{uuid}         → Results page (free tier shown)
/analyze/{uuid}/full    → Full report (requires payment)
/checkout/{uuid}        → Redirect to Stripe
/success                → Payment success → redirect to full report
/examples               → Sample analyses (social proof)
```

### 8.2 Landing Page Structure

```
┌─────────────────────────────────────────────────────────────┐
│  HEADER                                                      │
│  Logo                                    [View Example]      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  HERO                                                        │
│                                                              │
│  "Your GitHub is being judged.                              │
│   Find out what recruiters actually think."                 │
│                                                              │
│  ┌────────────────────────────────────────────┐             │
│  │  github.com/ [________________] [Roast Me] │             │
│  └────────────────────────────────────────────┘             │
│                                                              │
│  ✓ Free instant score  ✓ No signup required  ✓ Anonymous   │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  HOW IT WORKS                                                │
│                                                              │
│  1. Enter username → 2. Get scored → 3. Fix & improve       │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  WHAT WE ANALYZE                                            │
│                                                              │
│  [Profile] [Projects] [Consistency] [Tech] [Community]      │
│                                                              │
│  (Brief explanation of each)                                │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  SAMPLE SCORES (Social Proof)                               │
│                                                              │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐                       │
│  │ 73/100  │ │ 45/100  │ │ 88/100  │                       │
│  │ @user1  │ │ @user2  │ │ @user3  │                       │
│  └─────────┘ └─────────┘ └─────────┘                       │
│                                                              │
│  "I had no idea my README was that bad" — Real user         │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  FAQ                                                         │
│  - Is this free?                                            │
│  - How accurate is the score?                               │
│  - Do you store my data?                                    │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│  FOOTER                                                      │
│  © 2025 GitRoast | Privacy | Terms                          │
└─────────────────────────────────────────────────────────────┘
```

### 8.3 Results Page (Free Tier)

```
┌─────────────────────────────────────────────────────────────┐
│  [← Analyze Another]                        [Share Score]   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  @username's GitHub Profile                                  │
│                                                              │
│  ┌───────────────────────────────────────────┐              │
│  │                                           │              │
│  │              67 / 100                     │              │
│  │                                           │              │
│  │        "Needs improvement"                │              │
│  │                                           │              │
│  └───────────────────────────────────────────┘              │
│                                                              │
│  FIRST IMPRESSION                                           │
│  "A recruiter would see: active developer but unclear       │
│   what you specialize in. Projects exist but don't          │
│   showcase your best work effectively."                     │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  CATEGORY SCORES                                            │
│                                                              │
│  Profile     ████████░░░░ 72                               │
│  Projects    ██████░░░░░░ 58                               │
│  Consistency █████████░░░ 81                               │
│  Technical   ███████░░░░░ 65                               │
│  Community   █████░░░░░░░ 45                               │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  🚨 DEAL BREAKERS (3 critical issues)                       │
│                                                              │
│  1. No profile README                                       │
│     → Recruiters look for this first                        │
│                                                              │
│  2. Top projects have no descriptions                       │
│     → Impossible to understand at a glance                  │
│                                                              │
│  3. 45-day gap in contributions                             │
│     → Raises questions about consistency                    │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                                                      │   │
│  │  🔒 FULL REPORT INCLUDES:                           │   │
│  │                                                      │   │
│  │  ✓ Detailed analysis for each category             │   │
│  │  ✓ Project-by-project breakdown                    │   │
│  │  ✓ README quality analysis                         │   │
│  │  ✓ Specific fix recommendations                    │   │
│  │  ✓ Improvement checklist with time estimates       │   │
│  │  ✓ What recruiters would say in internal meetings  │   │
│  │                                                      │   │
│  │  ┌────────────────────────────────────────────┐    │   │
│  │  │      UNLOCK FULL REPORT — $9              │    │   │
│  │  │      One-time payment • Instant access    │    │   │
│  │  └────────────────────────────────────────────┘    │   │
│  │                                                      │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### 8.4 Share Card (OG Image)

Generate dynamic OG images for social sharing:

```
┌─────────────────────────────────────────┐
│                                         │
│  GitRoast                               │
│                                         │
│  @username scored                       │
│                                         │
│         67 / 100                        │
│                                         │
│  "Needs improvement"                    │
│                                         │
│  gitroast.dev                          │
│                                         │
└─────────────────────────────────────────┘
```

Tools: Use `@vercel/og` for Next.js or Puppeteer/Browsershot for Laravel.

### 8.5 Color Scheme

```css
/* Score colors */
--score-excellent: #22c55e;  /* 80-100: Green */
--score-good: #84cc16;       /* 70-79: Lime */
--score-average: #eab308;    /* 60-69: Yellow */
--score-below: #f97316;      /* 50-59: Orange */
--score-poor: #ef4444;       /* 0-49: Red */

/* Brand colors */
--primary: #6366f1;          /* Indigo */
--background: #0f172a;       /* Dark blue-gray */
--surface: #1e293b;          /* Lighter surface */
--text: #f8fafc;             /* Almost white */
--text-muted: #94a3b8;       /* Gray */
```

---

## 9. Development Roadmap

### 9.1 Week 1: Core Backend + Basic Frontend

**Days 1-2: Project Setup**
- [ ] Initialize Laravel project
- [ ] Set up database migrations
- [ ] Configure GitHub API integration
- [ ] Set up Claude API integration
- [ ] Create Analysis model and service

**Days 3-4: Analysis Engine**
- [ ] Build GitHub data fetcher
- [ ] Create analysis queue job
- [ ] Implement AI analysis with prompt
- [ ] Parse and store results
- [ ] Build score calculator

**Days 5-7: Basic Frontend**
- [ ] Create landing page
- [ ] Build username input form
- [ ] Create results page (free tier)
- [ ] Add loading states
- [ ] Basic styling with Tailwind

### 9.2 Week 2: Payments + Polish + Launch

**Days 8-9: Payment Integration**
- [ ] Set up Stripe products
- [ ] Create checkout flow
- [ ] Build webhook handler
- [ ] Implement report unlocking
- [ ] Create success page

**Days 10-11: Polish**
- [ ] Mobile responsiveness
- [ ] Error handling
- [ ] Rate limiting
- [ ] Caching layer
- [ ] OG image generation

**Days 12-13: Pre-Launch**
- [ ] Test full flow end-to-end
- [ ] Run 10-20 test analyses
- [ ] Create sample/example page
- [ ] Write launch content
- [ ] Prepare social posts

**Day 14: LAUNCH**
- [ ] Deploy to production
- [ ] Switch Stripe to live mode
- [ ] Post on Reddit, Twitter, etc.
- [ ] Monitor and respond

### 9.3 Time Estimates by Task

| Task | Hours |
|------|-------|
| Project setup + config | 2 |
| GitHub API integration | 3 |
| Claude API integration | 2 |
| Analysis service | 4 |
| Database + models | 2 |
| Queue processing | 2 |
| Landing page | 3 |
| Results page | 4 |
| Stripe integration | 3 |
| Full report page | 2 |
| OG image generation | 2 |
| Testing + debugging | 4 |
| Content + launch prep | 3 |
| **TOTAL** | **~36 hours** |

With your 17.5 hours/week, this is achievable in **2-2.5 weeks**.

---

## 10. Launch Strategy

### 10.1 Pre-Launch (3-5 days before)

- [ ] Create accounts: Twitter/X dev account, Reddit (if new), IndieHackers
- [ ] Join subreddits: r/webdev, r/cscareerquestions, r/learnprogramming, r/SideProject
- [ ] Engage genuinely (comment on others' posts, don't just promote)
- [ ] Prepare all post copy in advance
- [ ] Create 2-3 example analyses to show

### 10.2 Launch Day Sequence

**Morning (9-10 AM your local → catches US evening and EU morning)**

1. **Twitter/X Thread:**
```
I analyzed 50 GitHub profiles with AI.

Average score: 54/100.

Here's what's killing most developer profiles 🧵

---

1/ No profile README

This is the first thing recruiters see.
Most profiles have nothing.

It's like having a blank LinkedIn summary.

---

2/ Projects with no descriptions

You have 20 repos. Great.
But what do they DO?

"A React project" tells me nothing.
"A task management app with drag-and-drop, built in React + TypeScript" tells me everything.

---

3/ Contribution gaps

A 60-day gap raises questions:
- Were they employed? (good)
- Did they quit coding? (concern)
- Are they consistent? (unknown)

---

4/ No README files in repos

Your code might be brilliant.
But if I can't understand what it does in 10 seconds, I'm moving on.

---

5/ Only forked repos visible

Forks are fine for contributions.
But if your top repos are all forks, where's YOUR work?

---

I built a tool to analyze this automatically.

Enter any GitHub username → get a score + brutal feedback.

Free to try: [LINK]

What score do you think you'd get?
```

2. **Reddit (r/webdev or r/cscareerquestions):**
```
Title: I built a tool that analyzes GitHub profiles the way recruiters do

Hey everyone,

I've been hiring developers for years and I noticed most people have no idea how their GitHub looks to recruiters.

So I built a tool that:
- Scores your profile 0-100
- Shows critical issues ("deal breakers")
- Gives specific fix recommendations

I tested it on 50 profiles and the average score was 54/100. Most common issues:
- No profile README (recruiters look for this)
- Projects without descriptions
- Long contribution gaps
- No README files in repos

It's free to get your score and see 3 critical issues. Full report is $9 if you want the detailed breakdown.

Try it: [LINK]

Would love feedback from this community. What else should I analyze?
```

3. **LinkedIn:**
```
I reviewed 50 developer GitHub profiles this week.

Average score: 54/100.

The #1 issue? No profile README.

This is the first thing technical recruiters see when they check your GitHub. Most developers leave it completely blank.

It's like having an empty LinkedIn summary.

Other common issues:
→ Projects with no descriptions
→ Contribution gaps with no explanation
→ No README files explaining what code does

I turned this into a tool: enter any GitHub username, get a score, see exactly what's wrong.

Free to try: [link]

Curious: what do you think YOUR GitHub would score?
```

**Afternoon (2-4 PM)**

4. **IndieHackers post:**
```
Title: Launched GitRoast - GitHub profile analyzer for developers

Hey IH!

Just launched a tool I built in 2 weeks.

**What it does:**
Analyzes GitHub profiles and scores them 0-100, showing developers how recruiters perceive their profile.

**The idea:**
Developers obsess over resumes and LinkedIn but ignore their GitHub, even though technical recruiters always check it.

**Business model:**
- Free: Score + 3 critical issues
- $9: Full detailed report with fix recommendations

**Stack:**
- Laravel backend
- React frontend
- Claude API for analysis
- Stripe for payments

**Early results:**
Launched today. [X] free analyses so far, [Y] paid conversions.

Would love feedback. Try it: [LINK]
```

5. **Hacker News (Show HN):**
```
Title: Show HN: GitRoast – See how recruiters judge your GitHub profile

Link: [URL]

I built a tool that analyzes GitHub profiles the way a technical recruiter would.

Enter any username → get a score (0-100) → see critical issues.

I've been on both sides of technical hiring and noticed developers optimize everything except their GitHub, even though it's one of the first things recruiters check.

Free tier shows your score and top 3 issues. $9 unlocks the full report with specific fix recommendations.

Built with Laravel + Claude API. Happy to answer questions about the implementation.
```

### 10.3 Post-Launch (Days 2-7)

- Reply to EVERY comment (first week is critical)
- DM people who engage positively: "Hey, glad you liked it! If you try it, I'd love to hear your feedback"
- Share interesting scores on Twitter (anonymized)
- Cross-post to more subreddits (space out by 1-2 days)
- Reach out to dev influencers: "Built this tool, thought your audience might find it useful. Happy to give you early access to share"

### 10.4 Communities to Target

| Platform | Subreddit/Community | Approach |
|----------|---------------------|----------|
| Reddit | r/webdev | Value post + tool mention |
| Reddit | r/cscareerquestions | Focus on job hunting angle |
| Reddit | r/learnprogramming | Help juniors angle |
| Reddit | r/SideProject | Builder story |
| Reddit | r/programming | Technical implementation |
| Twitter/X | Dev Twitter | Thread + replies |
| LinkedIn | Connections + hashtags | Professional angle |
| IndieHackers | Community | Builder journey |
| Hacker News | Show HN | Technical crowd |
| Dev.to | Article | Tutorial style |
| Discord | Coding servers | Helpful tool sharing |

---

## 11. Post-Launch Growth

### 11.1 Week 2-4: Iterate Based on Feedback

- Monitor which categories users care about most
- A/B test pricing ($7 vs $9 vs $12)
- Add missing features users request
- Improve analysis prompt based on feedback

### 11.2 Month 2: SEO + Content

**Target keywords:**
- "github profile analyzer"
- "github profile score"
- "how to improve github profile"
- "github for recruiters"
- "github profile tips"

**Content ideas:**
- "How Recruiters Actually Evaluate Your GitHub (From Someone Who Hired 100+ Devs)"
- "50 GitHub Profiles Analyzed: Common Mistakes Developers Make"
- "GitHub Profile Checklist: 15 Things to Fix Before Your Next Job Application"

### 11.3 Month 3+: Expand

- **Add role-specific analysis:** "Analyze for Senior Frontend Developer"
- **Comparison feature:** "How do you compare to other developers at your level?"
- **Email capture:** "Get notified when your score changes"
- **Team plans:** For recruiters and bootcamps
- **API access:** For integration partners

---

## 12. Prompts & AI Instructions

### 12.1 Prompt for Coding the Backend (Use with Claude)

```
I'm building a GitHub Profile Analyzer called GitRoast using Laravel 11.

Current task: [SPECIFIC TASK]

Tech stack:
- Laravel 11
- MySQL
- Redis for queues
- GitHub REST API
- Claude API for analysis

Requirements:
- Clean, production-ready code
- Proper error handling
- Queue-based processing for analysis
- Caching for repeated analyses

Here's my current code structure:
[PASTE RELEVANT CODE]

Please help me implement [SPECIFIC FEATURE].
```

### 12.2 Prompt for Coding the Frontend (Use with Claude)

```
I'm building the frontend for GitRoast, a GitHub Profile Analyzer.

Tech stack:
- React 18 + TypeScript
- TailwindCSS
- React Query for data fetching
- Vite

Design requirements:
- Dark theme (background: #0f172a)
- Score colors: green (80+), lime (70-79), yellow (60-69), orange (50-59), red (<50)
- Mobile-first responsive design
- Loading states for async operations

Current task: [SPECIFIC COMPONENT OR PAGE]

Please provide clean, production-ready React code with TypeScript types.
```

### 12.3 Prompt for Improving the Analysis

```
I'm refining the AI analysis prompt for my GitHub Profile Analyzer.

Current prompt: [PASTE CURRENT PROMPT]

Issues I'm seeing:
- [Issue 1, e.g., "Scores are too generous"]
- [Issue 2, e.g., "Recommendations are too vague"]

Sample input: [PASTE SAMPLE GITHUB DATA]

Current output: [PASTE CURRENT AI OUTPUT]

Please suggest improvements to the prompt that will:
1. Make scores more calibrated
2. Make recommendations more specific and actionable
3. Better identify deal-breaker issues
```

### 12.4 Prompt for Writing Marketing Copy

```
I'm launching GitRoast, a GitHub Profile Analyzer for developers.

Product: AI tool that scores GitHub profiles 0-100 and shows developers how recruiters perceive them.

Target audience: Developers job hunting, bootcamp grads, freelancers

Pricing: Free score + 3 issues, $9 for full report

I need to write [SPECIFIC CONTENT TYPE]:
- Reddit post for r/webdev
- Twitter thread
- Landing page copy
- etc.

Tone: Direct, slightly irreverent, focused on real value. No hype or exaggeration.

Please write [CONTENT TYPE] that will resonate with developers and drive sign-ups.
```

---

## 13. Risk Mitigation

### 13.1 Technical Risks

| Risk | Mitigation |
|------|------------|
| GitHub API rate limits | Cache aggressively, use authenticated requests (5000/hour) |
| Claude API failures | Implement retry logic, have fallback prompt |
| High AI costs | Monitor costs, optimize prompt length, cache similar profiles |
| Abuse (competitors scraping) | Rate limit by IP, add CAPTCHA if needed |

### 13.2 Business Risks

| Risk | Mitigation |
|------|------------|
| Low conversion rate | A/B test free tier limits, adjust pricing |
| Chargebacks | Clear refund policy, deliver value |
| Copycat competitors | Move fast, build brand, add features |
| GitHub changes API | Stay informed, adapt quickly |

### 13.3 Legal Risks

| Risk | Mitigation |
|------|------------|
| Privacy concerns | Only analyze public data, clear privacy policy |
| GitHub ToS | Review terms, don't scrape, use official API |
| Payment issues (Lebanon) | Use friend's Stripe initially, migrate to UK company |

---

## 14. Success Metrics

### 14.1 Launch Week (Week 1)

| Metric | Target |
|--------|--------|
| Free analyses | 500+ |
| Paid conversions | 25+ |
| Conversion rate | 5%+ |
| Revenue | $225+ |

### 14.2 Month 1

| Metric | Target |
|--------|--------|
| Free analyses | 3,000+ |
| Paid conversions | 200+ |
| Revenue | $1,800+ |
| Organic traffic | 500+ visits |

### 14.3 Month 3

| Metric | Target |
|--------|--------|
| Free analyses | 10,000+/month |
| Paid conversions | 700+/month |
| Revenue | $6,000+/month |
| SEO traffic | 2,000+ visits/month |

### 14.4 Key Ratios to Monitor

- **Visit → Analysis:** Target 30%+
- **Analysis → Paid:** Target 5-10%
- **Cost per analysis:** Keep under $0.05
- **Revenue per visitor:** Target $0.15+

---

## Quick Reference: First 3 Days Checklist

### Day 1
- [ ] Create Laravel project
- [ ] Set up database + migrations
- [ ] Register for GitHub API (personal access token)
- [ ] Get Claude API key
- [ ] Build GitHub data fetcher service
- [ ] Test fetching data for 3-5 usernames

### Day 2
- [ ] Create analysis queue job
- [ ] Implement Claude API integration
- [ ] Build analysis prompt
- [ ] Test analysis on 5 profiles
- [ ] Store results in database
- [ ] Build basic API endpoint

### Day 3
- [ ] Create React project
- [ ] Build landing page
- [ ] Create input form
- [ ] Connect to backend API
- [ ] Build results page (free tier)
- [ ] Add loading states
- [ ] Deploy to staging

---

## You're Ready

This document contains everything you need to go from zero to launched.

**Your advantages:**
- You're fast at Laravel
- You understand the developer audience
- Payment is solved
- The market is underserved

**Remember:**
- Perfect is the enemy of shipped
- Launch with MVP, iterate based on feedback
- The first version will have bugs — that's fine
- Speed of iteration beats perfection

**Go build it.** 🚀

# GitRoast QA Testing Guide

This document provides step-by-step instructions for QA to manually test the API using Postman.

## Prerequisites

1. **Environment Setup**
   - Copy `.env.example` to `.env`
   - Configure database credentials
   - Run `php artisan migrate`
   - Run `php artisan serve` (API will be at `http://localhost:8000`)
   - Start queue worker: `php artisan queue:work`

2. **Postman Environment Variables**
   ```
   base_url: http://localhost:8000/api
   ```

---

## Test Cases

### 1. Health Check

**Endpoint:** `GET {{base_url}}/health`

**Expected Response:**
```json
{
    "status": "ok",
    "timestamp": "2024-01-01T12:00:00Z"
}
```

**Test Steps:**
1. Send GET request to `/api/health`
2. Verify status code is `200`
3. Verify response contains `"status": "ok"`

---

### 2. Create Analysis

**Endpoint:** `POST {{base_url}}/analyze`

**Request Body:**
```json
{
    "username": "torvalds"
}
```

**Expected Response (202 Accepted):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "username": "torvalds",
        "status": "pending",
        "created_at": "2024-01-01T12:00:00+00:00"
    },
    "links": {
        "self": "http://localhost:8000/api/analysis/550e8400-e29b-41d4-a716-446655440000",
        "status": "http://localhost:8000/api/analysis/550e8400-e29b-41d4-a716-446655440000/status"
    }
}
```

**Test Steps:**
1. Send POST request with a valid GitHub username
2. Verify status code is `202`
3. Save the `id` from response for subsequent tests
4. Verify all required fields are present

**Validation Tests:**

| Test Case | Request Body | Expected Status | Error |
|-----------|--------------|-----------------|-------|
| Empty username | `{}` | 422 | `username` field required |
| Invalid format | `{"username": "--invalid"}` | 422 | Username format invalid |
| Too long | `{"username": "a".repeat(40)}` | 422 | Max length exceeded |
| Valid | `{"username": "octocat"}` | 202 | Success |

---

### 3. Check Analysis Status

**Endpoint:** `GET {{base_url}}/analysis/{uuid}/status`

**Expected Response (Pending):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "status": "pending",
        "progress": 10
    }
}
```

**Expected Response (Processing):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "status": "processing",
        "progress": 50
    }
}
```

**Expected Response (Completed):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "status": "completed",
        "progress": 100,
        "redirect": "/analysis/550e8400-e29b-41d4-a716-446655440000"
    }
}
```

**Expected Response (Failed):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "status": "failed",
        "progress": 0,
        "error": "GitHub user not found"
    }
}
```

**Test Steps:**
1. Use the `id` from Create Analysis test
2. Poll this endpoint every 2-3 seconds
3. Verify progress increases from 10 → 50 → 100
4. Verify `redirect` appears when status is `completed`

---

### 4. Get Analysis Result (Free Tier)

**Endpoint:** `GET {{base_url}}/analysis/{uuid}`

**Expected Response:**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "username": "torvalds",
        "status": "completed",
        "overall_score": 85,
        "score_level": {
            "name": "strong",
            "label": "Strong",
            "color": "green"
        },
        "category_scores": {
            "profile": 90,
            "projects": 85,
            "consistency": 80,
            "technical": 88,
            "community": 82
        },
        "summary": "...",
        "first_impression": "...",
        "deal_breakers": [/* Max 3 items for free tier */],
        "strengths": [/* Max 2 items for free tier */],
        "is_paid": false,
        "created_at": "...",
        "completed_at": "..."
    }
}
```

**Test Steps:**
1. Use completed analysis UUID
2. Verify free tier limits:
   - `deal_breakers` has max 3 items
   - `strengths` has max 2 items
   - `improvement_checklist` is NOT present
3. Verify `is_paid` is `false`

---

### 5. Get Analysis Result (404 Error)

**Endpoint:** `GET {{base_url}}/analysis/non-existent-uuid`

**Expected Response (404):**
```json
{
    "message": "Analysis not found."
}
```

---

### 6. Get Full Report (Unpaid - 402 Error)

**Endpoint:** `GET {{base_url}}/analysis/{uuid}/full`

**Expected Response (402):**
```json
{
    "message": "Payment required for full report",
    "links": {
        "checkout": "http://localhost:8000/api/checkout/create"
    }
}
```

**Test Steps:**
1. Use an unpaid analysis UUID
2. Verify status code is `402`
3. Verify checkout link is present

---

### 7. Create Checkout Session

**Endpoint:** `POST {{base_url}}/checkout/create`

**Request Body:**
```json
{
    "analysis_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Expected Response:**
```json
{
    "data": {
        "transaction_id": "txn_abc123",
        "checkout_url": "https://checkout.paddle.com/..."
    }
}
```

**Test Steps:**
1. Use a completed but unpaid analysis UUID
2. Verify `checkout_url` is returned
3. Save `transaction_id` for verification test

**Error Cases:**

| Test Case | Expected Status | Error |
|-----------|-----------------|-------|
| Non-existent analysis | 404 | Analysis not found |
| Pending analysis | 400 | Analysis must be completed |
| Already paid | 400 | Analysis is already paid |

---

### 8. Verify Payment

**Endpoint:** `GET {{base_url}}/checkout/verify/{transactionId}`

**Expected Response:**
```json
{
    "data": {
        "is_paid": false
    }
}
```

**Test Steps:**
1. Use the `transaction_id` from checkout
2. Verify `is_paid` is `false` (unless payment was completed)

---

### 9. Get Full Report (Paid)

**Endpoint:** `GET {{base_url}}/analysis/{uuid}/full`

**Prerequisites:**
- Analysis must be marked as paid (manually in DB for testing, or via successful webhook)

**Expected Response:**
```json
{
    "data": {
        "id": "...",
        "username": "...",
        "status": "completed",
        "overall_score": 85,
        "score_level": {...},
        "category_scores": {...},
        "summary": "...",
        "first_impression": "...",
        "recruiter_perspective": "...",
        "categories": {...},
        "deal_breakers": [/* Full list */],
        "strengths": [/* Full list */],
        "top_projects_analysis": [...],
        "improvement_checklist": [...],
        "github_data": {...},
        "is_paid": true,
        "created_at": "...",
        "completed_at": "..."
    }
}
```

**Test Steps:**
1. Mark an analysis as paid in the database:
   ```sql
   UPDATE analyses SET is_paid = 1, paddle_payment_id = 'test123', paid_at = NOW() WHERE uuid = 'your-uuid';
   ```
2. Request full report
3. Verify ALL fields are present (no limitations)
4. Verify `improvement_checklist` is included
5. Verify `top_projects_analysis` is included

---

### 10. Rate Limiting

**Endpoint:** `POST {{base_url}}/analyze`

**Test Steps:**
1. Send 11 requests with different usernames from the same IP
2. Verify the 11th request returns:
   ```json
   {
       "message": "Rate limit exceeded. Please try again later.",
       "retry_after": 3600
   }
   ```
3. Status code should be `429`

---

### 11. Webhook Testing (Paddle)

**Endpoint:** `POST {{base_url}}/webhooks/paddle`

**Request Headers:**
```
Content-Type: application/json
Paddle-Signature: ts=...; h1=...
```

**Request Body (transaction.completed):**
```json
{
    "event_type": "transaction.completed",
    "data": {
        "id": "txn_abc123",
        "custom_data": {
            "analysis_id": "1"
        },
        "details": {
            "totals": {
                "grand_total": 999
            }
        },
        "currency_code": "USD",
        "billing_details": {
            "email": "test@example.com"
        }
    }
}
```

**Test Steps (for local testing without signature):**
1. Temporarily disable signature verification in `.env`:
   ```
   PADDLE_WEBHOOK_SECRET=
   ```
2. Create a pending payment in the database
3. Send the webhook payload
4. Verify:
   - Payment status changed to `completed`
   - Analysis `is_paid` changed to `true`
   - Customer email is stored

---

## Postman Collection Structure

```
GitRoast API
├── Health
│   └── GET Health Check
├── Analysis
│   ├── POST Create Analysis
│   ├── GET Analysis Status
│   ├── GET Analysis Result (Free)
│   └── GET Full Report (Paid)
├── Checkout
│   ├── POST Create Checkout
│   └── GET Verify Payment
└── Webhooks
    └── POST Paddle Webhook
```

---

## Environment Variables

Create the following environment in Postman:

| Variable | Value |
|----------|-------|
| `base_url` | `http://localhost:8000/api` |
| `analysis_id` | (set after creating analysis) |
| `transaction_id` | (set after creating checkout) |

---

## Test Data

**Valid GitHub usernames for testing:**
- `torvalds` (high score expected)
- `octocat` (GitHub mascot)
- `gaearon` (React developer)

**Invalid usernames:**
- `--invalid` (invalid format)
- `thisisausernamethatdoesnotexist123456789` (non-existent user)

---

## Troubleshooting

### Analysis stuck in "pending"
- Check if queue worker is running: `php artisan queue:work`
- Check queue logs: `php artisan queue:failed`

### 500 errors on analysis
- Verify `GITHUB_TOKEN` is set
- Verify `ANTHROPIC_API_KEY` is set
- Check Laravel logs: `storage/logs/laravel.log`

### Payment issues
- Verify Paddle sandbox mode is enabled
- Check `PADDLE_API_KEY` is set
- Verify price ID in `PADDLE_PRICE_FULL_REPORT`

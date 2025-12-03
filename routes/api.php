<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', [HealthController::class, 'index'])->name('health');

// Analysis endpoints
Route::prefix('analyze')->group(function (): void {
    Route::post('/', [AnalysisController::class, 'store'])->name('analyze.store');
});

Route::prefix('analysis')->group(function (): void {
    Route::get('/{uuid}', [AnalysisController::class, 'show'])->name('analysis.show');
    Route::get('/{uuid}/status', [AnalysisController::class, 'status'])->name('analysis.status');
    Route::get('/{uuid}/full', [AnalysisController::class, 'full'])->name('analysis.full');
});

// Checkout endpoints
Route::prefix('checkout')->group(function (): void {
    Route::post('/create', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::get('/verify/{transactionId}', [CheckoutController::class, 'verify'])->name('checkout.verify');
});

// Webhooks (no CSRF)
Route::post('/webhooks/paddle', [WebhookController::class, 'paddle'])->name('webhooks.paddle');

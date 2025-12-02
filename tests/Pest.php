<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeValidUuid', function () {
    return $this->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

expect()->extend('toBeValidScore', function () {
    return $this->toBeInt()->toBeBetween(0, 100);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function createAnalysis(array $attributes = []): \App\Models\Analysis
{
    return \App\Models\Analysis::factory()->create($attributes);
}

function createPaidAnalysis(array $attributes = []): \App\Models\Analysis
{
    return \App\Models\Analysis::factory()->paid()->create($attributes);
}

function createCompletedAnalysis(array $attributes = []): \App\Models\Analysis
{
    return \App\Models\Analysis::factory()->completed()->create($attributes);
}

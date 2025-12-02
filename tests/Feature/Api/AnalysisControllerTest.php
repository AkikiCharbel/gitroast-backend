<?php

declare(strict_types=1);

use App\Jobs\ProcessAnalysisJob;
use App\Models\Analysis;
use Illuminate\Support\Facades\Queue;

describe('POST /api/analyze', function (): void {
    beforeEach(function (): void {
        Queue::fake();
    });

    it('creates a new analysis', function (): void {
        $response = $this->postJson('/api/analyze', [
            'username' => 'torvalds',
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'data' => ['id', 'username', 'status', 'created_at'],
                'links' => ['self', 'status'],
            ]);

        expect(Analysis::count())->toBe(1);
        expect(Analysis::first()->github_username)->toBe('torvalds');
    });

    it('dispatches processing job', function (): void {
        $this->postJson('/api/analyze', ['username' => 'torvalds']);

        Queue::assertPushed(ProcessAnalysisJob::class, function ($job): bool {
            return $job->analysis->github_username === 'torvalds';
        });
    });

    it('validates username is required', function (): void {
        $response = $this->postJson('/api/analyze', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    });

    it('validates username format', function (): void {
        $response = $this->postJson('/api/analyze', [
            'username' => 'invalid--username',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    });
});

describe('GET /api/analysis/{uuid}', function (): void {
    it('returns analysis results', function (): void {
        $analysis = Analysis::factory()->completed()->create();

        $response = $this->getJson("/api/analysis/{$analysis->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    });

    it('returns 404 for invalid uuid', function (): void {
        $response = $this->getJson('/api/analysis/invalid-uuid');

        $response->assertStatus(404);
    });

    it('limits data for free tier', function (): void {
        $analysis = Analysis::factory()->completed()->create([
            'is_paid' => false,
            'ai_analysis' => [
                'deal_breakers' => [
                    ['issue' => '1'],
                    ['issue' => '2'],
                    ['issue' => '3'],
                    ['issue' => '4'],
                    ['issue' => '5'],
                ],
                'strengths' => ['a', 'b', 'c', 'd', 'e'],
                'summary' => 'Test',
                'first_impression' => 'Test',
            ],
        ]);

        $response = $this->getJson("/api/analysis/{$analysis->uuid}");

        expect(count($response->json('data.deal_breakers')))->toBe(3);
        expect(count($response->json('data.strengths')))->toBe(2);
    });
});

describe('GET /api/analysis/{uuid}/status', function (): void {
    it('returns pending status', function (): void {
        $analysis = Analysis::factory()->pending()->create();

        $response = $this->getJson("/api/analysis/{$analysis->uuid}/status");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.progress', 10);
    });

    it('returns processing status with progress', function (): void {
        $analysis = Analysis::factory()->processing()->create();

        $response = $this->getJson("/api/analysis/{$analysis->uuid}/status");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.progress', 50);
    });

    it('returns completed with redirect', function (): void {
        $analysis = Analysis::factory()->completed()->create();

        $response = $this->getJson("/api/analysis/{$analysis->uuid}/status");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.progress', 100)
            ->assertJsonStructure(['data' => ['redirect']]);
    });
});

describe('GET /api/analysis/{uuid}/full', function (): void {
    it('returns 402 for unpaid analysis', function (): void {
        $analysis = Analysis::factory()->completed()->create(['is_paid' => false]);

        $response = $this->getJson("/api/analysis/{$analysis->uuid}/full");

        $response->assertStatus(402)
            ->assertJsonPath('message', 'Payment required for full report');
    });

    it('returns full report for paid analysis', function (): void {
        $analysis = Analysis::factory()->paid()->create();

        $response = $this->getJson("/api/analysis/{$analysis->uuid}/full");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'summary',
                    'categories',
                    'top_projects_analysis',
                ],
            ]);
    });
});

<?php

declare(strict_types=1);

use App\Enums\AnalysisStatus;
use App\Enums\ScoreLevel;
use App\Models\Analysis;

describe('Analysis Model', function (): void {
    describe('casts', function (): void {
        it('casts status to enum', function (): void {
            $analysis = Analysis::factory()->create(['status' => 'completed']);

            expect($analysis->status)->toBeInstanceOf(AnalysisStatus::class);
            expect($analysis->status)->toBe(AnalysisStatus::COMPLETED);
        });

        it('casts github_data to array', function (): void {
            $analysis = Analysis::factory()->create([
                'github_data' => ['user' => ['name' => 'Test']],
            ]);

            expect($analysis->github_data)->toBeArray();
            expect($analysis->github_data['user']['name'])->toBe('Test');
        });

        it('casts is_paid to boolean', function (): void {
            $analysis = Analysis::factory()->create(['is_paid' => 1]);

            expect($analysis->is_paid)->toBeBool();
            expect($analysis->is_paid)->toBeTrue();
        });
    });

    describe('scopes', function (): void {
        it('filters by completed status', function (): void {
            Analysis::factory()->create(['status' => 'completed']);
            Analysis::factory()->create(['status' => 'pending']);
            Analysis::factory()->create(['status' => 'failed']);

            $completed = Analysis::completed()->get();

            expect($completed)->toHaveCount(1);
            expect($completed->first()->status)->toBe(AnalysisStatus::COMPLETED);
        });

        it('filters by paid status', function (): void {
            Analysis::factory()->create(['is_paid' => true]);
            Analysis::factory()->create(['is_paid' => false]);
            Analysis::factory()->create(['is_paid' => false]);

            expect(Analysis::paid()->count())->toBe(1);
            expect(Analysis::unpaid()->count())->toBe(2);
        });
    });

    describe('accessors', function (): void {
        it('returns correct score level', function (): void {
            $analysis = Analysis::factory()->create(['overall_score' => 85]);

            expect($analysis->score_level)->toBeInstanceOf(ScoreLevel::class);
            expect($analysis->score_level)->toBe(ScoreLevel::STRONG);
        });

        it('returns category scores array', function (): void {
            $analysis = Analysis::factory()->create([
                'profile_score' => 80,
                'projects_score' => 70,
                'consistency_score' => 60,
                'technical_score' => 90,
                'community_score' => 50,
            ]);

            expect($analysis->category_scores)->toBe([
                'profile' => 80,
                'projects' => 70,
                'consistency' => 60,
                'technical' => 90,
                'community' => 50,
            ]);
        });

        it('returns deal breakers from ai_analysis', function (): void {
            $dealBreakers = [
                ['issue' => 'No README', 'fix' => 'Add README'],
            ];

            $analysis = Analysis::factory()->create([
                'ai_analysis' => ['deal_breakers' => $dealBreakers],
            ]);

            expect($analysis->deal_breakers)->toBe($dealBreakers);
        });
    });

    describe('methods', function (): void {
        it('marks analysis as processing', function (): void {
            $analysis = Analysis::factory()->pending()->create();

            $analysis->markAsProcessing();

            expect($analysis->fresh()->status)->toBe(AnalysisStatus::PROCESSING);
        });

        it('marks analysis as completed', function (): void {
            $analysis = Analysis::factory()->processing()->create();

            $analysis->markAsCompleted([
                'overall_score' => 75,
                'ai_analysis' => ['summary' => 'Test'],
            ]);

            $fresh = $analysis->fresh();
            expect($fresh->status)->toBe(AnalysisStatus::COMPLETED);
            expect($fresh->overall_score)->toBe(75);
            expect($fresh->completed_at)->not->toBeNull();
        });

        it('marks analysis as failed', function (): void {
            $analysis = Analysis::factory()->processing()->create();

            $analysis->markAsFailed('API error');

            $fresh = $analysis->fresh();
            expect($fresh->status)->toBe(AnalysisStatus::FAILED);
            expect($fresh->error_message)->toBe('API error');
        });

        it('unlocks analysis after payment', function (): void {
            $analysis = Analysis::factory()->create(['is_paid' => false]);

            $analysis->unlock('pi_test123');

            $fresh = $analysis->fresh();
            expect($fresh->is_paid)->toBeTrue();
            expect($fresh->stripe_payment_id)->toBe('pi_test123');
            expect($fresh->paid_at)->not->toBeNull();
        });
    });
});

<?php

declare(strict_types=1);

use App\DTOs\Analysis\AnalysisResultDTO;
use App\Services\Analysis\ScoreCalculatorService;

beforeEach(function (): void {
    $this->calculator = new ScoreCalculatorService;
});

describe('calculateOverallScore', function (): void {
    it('calculates weighted score correctly', function (): void {
        $result = new AnalysisResultDTO(
            overallScore: 0,
            summary: '',
            firstImpression: '',
            categories: [
                'profile_completeness' => ['score' => 80],      // 0.15 weight
                'project_quality' => ['score' => 70],           // 0.30 weight
                'contribution_consistency' => ['score' => 60],  // 0.20 weight
                'technical_signals' => ['score' => 90],         // 0.20 weight
                'community_engagement' => ['score' => 50],      // 0.15 weight
            ],
            dealBreakers: [],
            topProjectsAnalysis: [],
            improvementChecklist: [],
            strengths: [],
            recruiterPerspective: null,
        );

        $score = $this->calculator->calculateOverallScore($result);

        // Expected: 80*0.15 + 70*0.30 + 60*0.20 + 90*0.20 + 50*0.15 = 71.5 → 72
        expect($score)->toBe(72);
    });

    it('handles missing category scores', function (): void {
        $result = new AnalysisResultDTO(
            overallScore: 0,
            summary: '',
            firstImpression: '',
            categories: [
                'profile_completeness' => ['score' => 80],
            ],
            dealBreakers: [],
            topProjectsAnalysis: [],
            improvementChecklist: [],
            strengths: [],
            recruiterPerspective: null,
        );

        $score = $this->calculator->calculateOverallScore($result);

        expect($score)->toBe(12); // 80 * 0.15 = 12
    });

    it('returns zero for empty categories', function (): void {
        $result = new AnalysisResultDTO(
            overallScore: 0,
            summary: '',
            firstImpression: '',
            categories: [],
            dealBreakers: [],
            topProjectsAnalysis: [],
            improvementChecklist: [],
            strengths: [],
            recruiterPerspective: null,
        );

        $score = $this->calculator->calculateOverallScore($result);

        expect($score)->toBe(0);
    });
});

describe('normalizeScore', function (): void {
    it('keeps valid scores unchanged', function (): void {
        expect($this->calculator->normalizeScore(50))->toBe(50);
        expect($this->calculator->normalizeScore(0))->toBe(0);
        expect($this->calculator->normalizeScore(100))->toBe(100);
    });

    it('clamps scores to valid range', function (): void {
        expect($this->calculator->normalizeScore(-10))->toBe(0);
        expect($this->calculator->normalizeScore(150))->toBe(100);
    });
});

describe('calculateTrend', function (): void {
    it('returns stable for insufficient data', function (): void {
        expect($this->calculator->calculateTrend([75]))->toBe('stable');
    });

    it('returns improving for increasing scores', function (): void {
        expect($this->calculator->calculateTrend([60, 70, 80]))->toBe('improving');
    });

    it('returns declining for decreasing scores', function (): void {
        expect($this->calculator->calculateTrend([80, 70, 60]))->toBe('declining');
    });

    it('returns stable for small changes', function (): void {
        expect($this->calculator->calculateTrend([70, 72, 71]))->toBe('stable');
    });
});

<?php

declare(strict_types=1);

use App\Enums\AnalysisStatus;

describe('AnalysisStatus enum', function (): void {
    it('has correct values', function (): void {
        expect(AnalysisStatus::PENDING->value)->toBe('pending');
        expect(AnalysisStatus::PROCESSING->value)->toBe('processing');
        expect(AnalysisStatus::COMPLETED->value)->toBe('completed');
        expect(AnalysisStatus::FAILED->value)->toBe('failed');
    });

    it('returns correct labels', function (): void {
        expect(AnalysisStatus::PENDING->label())->toBe('Pending');
        expect(AnalysisStatus::PROCESSING->label())->toBe('Processing');
        expect(AnalysisStatus::COMPLETED->label())->toBe('Completed');
        expect(AnalysisStatus::FAILED->label())->toBe('Failed');
    });

    it('returns correct colors', function (): void {
        expect(AnalysisStatus::PENDING->color())->toBe('gray');
        expect(AnalysisStatus::PROCESSING->color())->toBe('warning');
        expect(AnalysisStatus::COMPLETED->color())->toBe('success');
        expect(AnalysisStatus::FAILED->color())->toBe('danger');
    });

    it('returns correct progress percentages', function (): void {
        expect(AnalysisStatus::PENDING->progress())->toBe(10);
        expect(AnalysisStatus::PROCESSING->progress())->toBe(50);
        expect(AnalysisStatus::COMPLETED->progress())->toBe(100);
        expect(AnalysisStatus::FAILED->progress())->toBe(0);
    });
});

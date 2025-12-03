<?php

declare(strict_types=1);

use App\Enums\ScoreLevel;

describe('fromScore', function (): void {
    it('returns exceptional for scores 90-100', function (): void {
        expect(ScoreLevel::fromScore(90))->toBe(ScoreLevel::EXCEPTIONAL);
        expect(ScoreLevel::fromScore(95))->toBe(ScoreLevel::EXCEPTIONAL);
        expect(ScoreLevel::fromScore(100))->toBe(ScoreLevel::EXCEPTIONAL);
    });

    it('returns strong for scores 80-89', function (): void {
        expect(ScoreLevel::fromScore(80))->toBe(ScoreLevel::STRONG);
        expect(ScoreLevel::fromScore(85))->toBe(ScoreLevel::STRONG);
        expect(ScoreLevel::fromScore(89))->toBe(ScoreLevel::STRONG);
    });

    it('returns good for scores 70-79', function (): void {
        expect(ScoreLevel::fromScore(70))->toBe(ScoreLevel::GOOD);
        expect(ScoreLevel::fromScore(75))->toBe(ScoreLevel::GOOD);
    });

    it('returns average for scores 60-69', function (): void {
        expect(ScoreLevel::fromScore(60))->toBe(ScoreLevel::AVERAGE);
        expect(ScoreLevel::fromScore(65))->toBe(ScoreLevel::AVERAGE);
    });

    it('returns below_average for scores 50-59', function (): void {
        expect(ScoreLevel::fromScore(50))->toBe(ScoreLevel::BELOW_AVERAGE);
        expect(ScoreLevel::fromScore(55))->toBe(ScoreLevel::BELOW_AVERAGE);
    });

    it('returns poor for scores below 50', function (): void {
        expect(ScoreLevel::fromScore(49))->toBe(ScoreLevel::POOR);
        expect(ScoreLevel::fromScore(0))->toBe(ScoreLevel::POOR);
    });

    it('handles null scores', function (): void {
        expect(ScoreLevel::fromScore(null))->toBe(ScoreLevel::POOR);
    });
});

describe('label', function (): void {
    it('returns human readable labels', function (): void {
        expect(ScoreLevel::EXCEPTIONAL->label())->toBe('Exceptional');
        expect(ScoreLevel::STRONG->label())->toBe('Strong');
        expect(ScoreLevel::POOR->label())->toBe('Needs Work');
    });
});

describe('color', function (): void {
    it('returns valid hex colors', function (): void {
        expect(ScoreLevel::EXCEPTIONAL->color())->toMatch('/^#[0-9a-f]{6}$/i');
        expect(ScoreLevel::POOR->color())->toMatch('/^#[0-9a-f]{6}$/i');
    });
});

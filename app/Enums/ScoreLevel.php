<?php

declare(strict_types=1);

namespace App\Enums;

enum ScoreLevel: string
{
    case EXCEPTIONAL = 'exceptional';
    case STRONG = 'strong';
    case GOOD = 'good';
    case AVERAGE = 'average';
    case BELOW_AVERAGE = 'below_average';
    case POOR = 'poor';

    public static function fromScore(?int $score): self
    {
        if ($score === null) {
            return self::POOR;
        }

        return match (true) {
            $score >= 90 => self::EXCEPTIONAL,
            $score >= 80 => self::STRONG,
            $score >= 70 => self::GOOD,
            $score >= 60 => self::AVERAGE,
            $score >= 50 => self::BELOW_AVERAGE,
            default => self::POOR,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::EXCEPTIONAL => 'Exceptional',
            self::STRONG => 'Strong',
            self::GOOD => 'Good',
            self::AVERAGE => 'Average',
            self::BELOW_AVERAGE => 'Below Average',
            self::POOR => 'Needs Work',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EXCEPTIONAL => '#22c55e',
            self::STRONG => '#84cc16',
            self::GOOD => '#eab308',
            self::AVERAGE => '#f97316',
            self::BELOW_AVERAGE => '#ef4444',
            self::POOR => '#dc2626',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::EXCEPTIONAL => 'Top-tier profile that stands out to recruiters',
            self::STRONG => 'Well-maintained profile with good presence',
            self::GOOD => 'Solid profile with room for improvement',
            self::AVERAGE => 'Standard profile, needs attention',
            self::BELOW_AVERAGE => 'Profile needs significant improvements',
            self::POOR => 'Major issues that could hurt job prospects',
        };
    }
}

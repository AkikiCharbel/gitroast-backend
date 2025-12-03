<?php

declare(strict_types=1);

namespace App\Enums;

enum ScoreCategory: string
{
    case PROFILE_COMPLETENESS = 'profile_completeness';
    case PROJECT_QUALITY = 'project_quality';
    case CONTRIBUTION_CONSISTENCY = 'contribution_consistency';
    case TECHNICAL_SIGNALS = 'technical_signals';
    case COMMUNITY_ENGAGEMENT = 'community_engagement';

    public function label(): string
    {
        return match ($this) {
            self::PROFILE_COMPLETENESS => 'Profile Completeness',
            self::PROJECT_QUALITY => 'Project Quality',
            self::CONTRIBUTION_CONSISTENCY => 'Contribution Consistency',
            self::TECHNICAL_SIGNALS => 'Technical Signals',
            self::COMMUNITY_ENGAGEMENT => 'Community Engagement',
        };
    }

    public function weight(): float
    {
        return match ($this) {
            self::PROFILE_COMPLETENESS => 0.15,
            self::PROJECT_QUALITY => 0.30,
            self::CONTRIBUTION_CONSISTENCY => 0.20,
            self::TECHNICAL_SIGNALS => 0.20,
            self::COMMUNITY_ENGAGEMENT => 0.15,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PROFILE_COMPLETENESS => 'How complete and professional your profile looks',
            self::PROJECT_QUALITY => 'Quality of your repositories, READMEs, and documentation',
            self::CONTRIBUTION_CONSISTENCY => 'How active and consistent your contributions are',
            self::TECHNICAL_SIGNALS => 'Technical indicators like language diversity and best practices',
            self::COMMUNITY_ENGAGEMENT => 'Involvement in the open-source community',
        };
    }

    /**
     * @return array<int, self>
     */
    public static function all(): array
    {
        return [
            self::PROFILE_COMPLETENESS,
            self::PROJECT_QUALITY,
            self::CONTRIBUTION_CONSISTENCY,
            self::TECHNICAL_SIGNALS,
            self::COMMUNITY_ENGAGEMENT,
        ];
    }
}

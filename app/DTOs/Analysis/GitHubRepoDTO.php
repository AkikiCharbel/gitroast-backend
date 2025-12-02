<?php

declare(strict_types=1);

namespace App\DTOs\Analysis;

use Spatie\LaravelData\Data;

class GitHubRepoDTO extends Data
{
    /**
     * @param  array<int, string>  $topics
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $language,
        public readonly int $stargazersCount,
        public readonly int $forksCount,
        public readonly int $openIssuesCount,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly string $pushedAt,
        public readonly array $topics,
        public readonly ?string $license,
        public readonly bool $isFork,
        public readonly ?string $readme,
    ) {}
}

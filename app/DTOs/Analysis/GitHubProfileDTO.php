<?php

declare(strict_types=1);

namespace App\DTOs\Analysis;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class GitHubProfileDTO extends Data
{
    /**
     * @param  Collection<int, GitHubRepoDTO>  $repositories
     * @param  array<string, mixed>  $contributions
     */
    public function __construct(
        public readonly string $username,
        public readonly ?string $name,
        public readonly ?string $bio,
        public readonly ?string $avatarUrl,
        public readonly ?string $location,
        public readonly ?string $blog,
        public readonly ?string $company,
        public readonly ?string $twitterUsername,
        public readonly int $publicRepos,
        public readonly int $followers,
        public readonly int $following,
        public readonly string $createdAt,
        public readonly ?string $profileReadme,
        public readonly Collection $repositories,
        public readonly array $contributions,
    ) {}
}

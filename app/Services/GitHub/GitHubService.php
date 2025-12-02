<?php

declare(strict_types=1);

namespace App\Services\GitHub;

use App\DTOs\Analysis\GitHubProfileDTO;
use App\DTOs\Analysis\GitHubRepoDTO;
use App\Exceptions\GitHubApiException;
use App\Integrations\GitHub\GitHubConnector;
use App\Integrations\GitHub\Requests\GetRepoReadmeRequest;
use App\Integrations\GitHub\Requests\GetUserEventsRequest;
use App\Integrations\GitHub\Requests\GetUserReposRequest;
use App\Integrations\GitHub\Requests\GetUserRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Saloon\Exceptions\Request\RequestException;

class GitHubService
{
    private const int CACHE_TTL = 3600;

    public function __construct(
        private readonly GitHubConnector $connector
    ) {}

    public function getProfileData(string $username): GitHubProfileDTO
    {
        return Cache::remember(
            "github:profile:{$username}",
            self::CACHE_TTL,
            fn (): GitHubProfileDTO => $this->fetchProfileData($username)
        );
    }

    private function fetchProfileData(string $username): GitHubProfileDTO
    {
        try {
            $userResponse = $this->connector->send(new GetUserRequest($username));
            /** @var array<string, mixed> $userData */
            $userData = $userResponse->json();

            $reposResponse = $this->connector->send(
                new GetUserReposRequest($username, perPage: 30)
            );
            /** @var array<int, array<string, mixed>> $reposData */
            $reposData = $reposResponse->json();

            $profileReadme = $this->getProfileReadme($username);
            $contributions = $this->getContributionData($username);
            $repositories = $this->processRepositories($username, $reposData);

            return new GitHubProfileDTO(
                username: (string) ($userData['login'] ?? $username),
                name: $userData['name'] ?? null,
                bio: $userData['bio'] ?? null,
                avatarUrl: $userData['avatar_url'] ?? null,
                location: $userData['location'] ?? null,
                blog: $userData['blog'] ?? null,
                company: $userData['company'] ?? null,
                twitterUsername: $userData['twitter_username'] ?? null,
                publicRepos: (int) ($userData['public_repos'] ?? 0),
                followers: (int) ($userData['followers'] ?? 0),
                following: (int) ($userData['following'] ?? 0),
                createdAt: (string) ($userData['created_at'] ?? now()->toIso8601String()),
                profileReadme: $profileReadme,
                repositories: $repositories,
                contributions: $contributions,
            );
        } catch (RequestException $e) {
            if ($e->getResponse()?->status() === 404) {
                throw new GitHubApiException("GitHub user '{$username}' not found", 404, $e);
            }

            if ($e->getResponse()?->status() === 403) {
                throw new GitHubApiException('GitHub API rate limit exceeded', 429, $e);
            }

            throw new GitHubApiException(
                "Failed to fetch GitHub data: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    private function getProfileReadme(string $username): ?string
    {
        try {
            $response = $this->connector->send(
                new GetRepoReadmeRequest($username, $username)
            );

            /** @var array<string, mixed> $data */
            $data = $response->json();
            $content = $data['content'] ?? null;

            if ($content && is_string($content)) {
                return base64_decode($content);
            }
        } catch (RequestException) {
            // Profile README doesn't exist
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function getContributionData(string $username): array
    {
        try {
            $response = $this->connector->send(
                new GetUserEventsRequest($username)
            );

            /** @var array<string, mixed> $data */
            $data = $response->json();

            return $data;
        } catch (RequestException) {
            return [];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $repos
     * @return Collection<int, GitHubRepoDTO>
     */
    private function processRepositories(string $username, array $repos): Collection
    {
        /** @var Collection<int, array<string, mixed>> $sorted */
        $sorted = collect($repos)
            ->filter(fn (array $repo): bool => ! ($repo['fork'] ?? false))
            ->sortByDesc(fn (array $repo): int => (int) (($repo['stargazers_count'] ?? 0) * 2 +
                (strtotime((string) ($repo['pushed_at'] ?? 'now')) > strtotime('-90 days') ? 10 : 0))
            )
            ->take(15)
            ->values();

        return $sorted->map(function (array $repo, int $index) use ($username): GitHubRepoDTO {
            $readme = null;

            if ($index < 6) {
                $readme = $this->getRepoReadme($username, (string) ($repo['name'] ?? ''));
            }

            return new GitHubRepoDTO(
                name: (string) ($repo['name'] ?? ''),
                description: $repo['description'] ?? null,
                language: $repo['language'] ?? null,
                stargazersCount: (int) ($repo['stargazers_count'] ?? 0),
                forksCount: (int) ($repo['forks_count'] ?? 0),
                openIssuesCount: (int) ($repo['open_issues_count'] ?? 0),
                createdAt: (string) ($repo['created_at'] ?? now()->toIso8601String()),
                updatedAt: (string) ($repo['updated_at'] ?? now()->toIso8601String()),
                pushedAt: (string) ($repo['pushed_at'] ?? now()->toIso8601String()),
                topics: $repo['topics'] ?? [],
                license: $repo['license']['name'] ?? null,
                isFork: (bool) ($repo['fork'] ?? false),
                readme: $readme ? mb_substr($readme, 0, 3000) : null,
            );
        });
    }

    private function getRepoReadme(string $username, string $repo): ?string
    {
        try {
            $response = $this->connector->send(
                new GetRepoReadmeRequest($username, $repo)
            );

            /** @var array<string, mixed> $data */
            $data = $response->json();
            $content = $data['content'] ?? null;

            if ($content && is_string($content)) {
                return base64_decode($content);
            }
        } catch (RequestException) {
            // README doesn't exist
        }

        return null;
    }

    public function userExists(string $username): bool
    {
        try {
            $this->connector->send(new GetUserRequest($username));

            return true;
        } catch (RequestException) {
            return false;
        }
    }
}

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
        /** @var GitHubProfileDTO $result */
        $result = Cache::remember(
            "github:profile:{$username}",
            self::CACHE_TTL,
            fn (): GitHubProfileDTO => $this->fetchProfileData($username)
        );

        return $result;
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
                username: $this->getString($userData, 'login', $username),
                name: $this->getStringOrNull($userData, 'name'),
                bio: $this->getStringOrNull($userData, 'bio'),
                avatarUrl: $this->getStringOrNull($userData, 'avatar_url'),
                location: $this->getStringOrNull($userData, 'location'),
                blog: $this->getStringOrNull($userData, 'blog'),
                company: $this->getStringOrNull($userData, 'company'),
                twitterUsername: $this->getStringOrNull($userData, 'twitter_username'),
                publicRepos: $this->getInt($userData, 'public_repos'),
                followers: $this->getInt($userData, 'followers'),
                following: $this->getInt($userData, 'following'),
                createdAt: $this->getString($userData, 'created_at', now()->toIso8601String()),
                profileReadme: $profileReadme,
                repositories: $repositories,
                contributions: $contributions,
            );
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()->status();

            if ($statusCode === 404) {
                throw new GitHubApiException("GitHub user '{$username}' not found", 404, $e);
            }

            if ($statusCode === 403) {
                throw new GitHubApiException('GitHub API rate limit exceeded', 429, $e);
            }

            throw new GitHubApiException(
                "Failed to fetch GitHub data: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function getString(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function getStringOrNull(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function getInt(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
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

            if ($content !== null && is_string($content)) {
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
            ->sortByDesc(function (array $repo): int {
                $stars = $this->getInt($repo, 'stargazers_count');
                $pushedAt = $this->getString($repo, 'pushed_at', 'now');
                $recentBonus = strtotime($pushedAt) > strtotime('-90 days') ? 10 : 0;

                return $stars * 2 + $recentBonus;
            })
            ->take(15)
            ->values();

        return $sorted->map(function (array $repo, int $index) use ($username): GitHubRepoDTO {
            $readme = null;

            if ($index < 6) {
                $repoName = $this->getString($repo, 'name');
                if ($repoName !== '') {
                    $readme = $this->getRepoReadme($username, $repoName);
                }
            }

            $license = $repo['license'] ?? null;
            $licenseName = null;
            if (is_array($license) && isset($license['name'])) {
                $licenseName = is_string($license['name']) ? $license['name'] : null;
            }

            /** @var array<int, string> $topics */
            $topics = isset($repo['topics']) && is_array($repo['topics']) ? $repo['topics'] : [];

            return new GitHubRepoDTO(
                name: $this->getString($repo, 'name'),
                description: $this->getStringOrNull($repo, 'description'),
                language: $this->getStringOrNull($repo, 'language'),
                stargazersCount: $this->getInt($repo, 'stargazers_count'),
                forksCount: $this->getInt($repo, 'forks_count'),
                openIssuesCount: $this->getInt($repo, 'open_issues_count'),
                createdAt: $this->getString($repo, 'created_at', now()->toIso8601String()),
                updatedAt: $this->getString($repo, 'updated_at', now()->toIso8601String()),
                pushedAt: $this->getString($repo, 'pushed_at', now()->toIso8601String()),
                topics: $topics,
                license: $licenseName,
                isFork: (bool) ($repo['fork'] ?? false),
                readme: $readme !== null ? mb_substr($readme, 0, 3000) : null,
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

            if ($content !== null && is_string($content)) {
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

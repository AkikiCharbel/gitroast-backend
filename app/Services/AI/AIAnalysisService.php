<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\Analysis\AnalysisResultDTO;
use App\DTOs\Analysis\GitHubProfileDTO;
use App\Exceptions\AIAnalysisException;
use App\Integrations\Anthropic\AnthropicConnector;
use App\Integrations\Anthropic\Requests\AnalyzeProfileRequest;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\RequestException;

class AIAnalysisService
{
    public function __construct(
        private readonly AnthropicConnector $connector
    ) {}

    public function analyze(GitHubProfileDTO $profile): AnalysisResultDTO
    {
        $prompt = $this->buildPrompt($profile);

        try {
            $response = $this->connector->send(
                new AnalyzeProfileRequest($prompt)
            );

            /** @var array<string, mixed> $json */
            $json = $response->json();

            $contentArray = $json['content'] ?? [];
            $firstContent = is_array($contentArray) && isset($contentArray[0]) ? $contentArray[0] : [];
            $content = is_array($firstContent) && isset($firstContent['text']) ? $firstContent['text'] : null;

            if (! $content || ! is_string($content)) {
                throw new AIAnalysisException('Empty response from AI');
            }

            $analysis = $this->parseResponse($content);

            return AnalysisResultDTO::fromArray($analysis);
        } catch (RequestException $e) {
            Log::error('AI Analysis failed', [
                'username' => $profile->username,
                'error' => $e->getMessage(),
            ]);

            throw new AIAnalysisException(
                "AI analysis failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    private function buildPrompt(GitHubProfileDTO $profile): string
    {
        $profileJson = json_encode([
            'username' => $profile->username,
            'name' => $profile->name,
            'bio' => $profile->bio,
            'location' => $profile->location,
            'blog' => $profile->blog,
            'company' => $profile->company,
            'twitter' => $profile->twitterUsername,
            'public_repos' => $profile->publicRepos,
            'followers' => $profile->followers,
            'following' => $profile->following,
            'account_age_days' => (int) now()->diffInDays($profile->createdAt),
        ], JSON_PRETTY_PRINT);

        $reposJson = json_encode(
            $profile->repositories->map(fn ($repo): array => [
                'name' => $repo->name,
                'description' => $repo->description,
                'language' => $repo->language,
                'stars' => $repo->stargazersCount,
                'forks' => $repo->forksCount,
                'topics' => $repo->topics,
                'has_readme' => ! empty($repo->readme),
                'readme_length' => strlen($repo->readme ?? ''),
                'last_pushed' => $repo->pushedAt,
            ])->toArray(),
            JSON_PRETTY_PRINT
        );

        $readmeContent = $profile->profileReadme
            ? mb_substr($profile->profileReadme, 0, 2000)
            : 'No profile README found';

        return <<<PROMPT
        Analyze this GitHub profile:

        Username: {$profile->username}

        Profile Data:
        {$profileJson}

        Repositories (top by stars and recent activity):
        {$reposJson}

        Profile README:
        {$readmeContent}

        Provide your analysis in the exact JSON format specified in the system prompt.
        PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseResponse(string $content): array
    {
        $content = preg_replace('/```json\s*/', '', $content) ?? $content;
        $content = preg_replace('/```\s*/', '', $content) ?? $content;
        $content = trim($content);

        /** @var array<string, mixed>|null $data */
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse AI response as JSON', [
                'content' => mb_substr($content, 0, 500),
                'error' => json_last_error_msg(),
            ]);

            throw new AIAnalysisException(
                'Failed to parse AI response: '.json_last_error_msg()
            );
        }

        if ($data === null) {
            throw new AIAnalysisException('AI response parsed to null');
        }

        $required = ['overall_score', 'categories', 'deal_breakers'];
        foreach ($required as $field) {
            if (! isset($data[$field])) {
                throw new AIAnalysisException("Missing required field: {$field}");
            }
        }

        return $data;
    }
}

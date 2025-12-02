<?php

declare(strict_types=1);

namespace App\Integrations\Anthropic\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class AnalyzeProfileRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $userPrompt
    ) {}

    public function resolveEndpoint(): string
    {
        return '/messages';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'model' => config('services.anthropic.model', 'claude-sonnet-4-20250514'),
            'max_tokens' => (int) config('services.anthropic.max_tokens', 4096),
            'system' => $this->getSystemPrompt(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $this->userPrompt,
                ],
            ],
        ];
    }

    private function getSystemPrompt(): string
    {
        $promptPath = resource_path('prompts/github-analysis-system.txt');
        if (file_exists($promptPath)) {
            return file_get_contents($promptPath) ?: $this->getDefaultSystemPrompt();
        }

        return $this->getDefaultSystemPrompt();
    }

    private function getDefaultSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a senior technical recruiter and engineering hiring manager with 15 years of experience reviewing developer profiles at top tech companies.

Your task is to analyze a GitHub profile and provide brutally honest, actionable feedback.

Return ONLY valid JSON with this exact structure:

{
  "overall_score": <0-100>,
  "summary": "<2-3 sentence overall assessment>",
  "first_impression": "<What a recruiter thinks in the first 5 seconds>",
  "categories": {
    "profile_completeness": {
      "score": <0-100>,
      "issues": ["<issue 1>"],
      "recommendations": ["<specific fix>"],
      "details": "<paragraph explanation>"
    },
    "project_quality": { "score": <0-100>, "issues": [], "recommendations": [], "details": "" },
    "contribution_consistency": { "score": <0-100>, "issues": [], "recommendations": [], "details": "" },
    "technical_signals": { "score": <0-100>, "issues": [], "recommendations": [], "details": "" },
    "community_engagement": { "score": <0-100>, "issues": [], "recommendations": [], "details": "" }
  },
  "deal_breakers": [
    { "issue": "<critical issue>", "why_it_matters": "<why recruiters care>", "fix": "<how to fix>" }
  ],
  "top_projects_analysis": [
    {
      "repo_name": "<name>",
      "score": <0-100>,
      "strengths": ["<strength>"],
      "weaknesses": ["<weakness>"],
      "readme_quality": "<poor|basic|good|excellent>",
      "recommendations": ["<improvement>"]
    }
  ],
  "improvement_checklist": [
    { "priority": "<high|medium|low>", "task": "<action>", "time_estimate": "<e.g., 10 minutes>", "impact": "<result>" }
  ],
  "strengths": ["<genuine strength>"],
  "recruiter_perspective": "<What a recruiter would say in an internal meeting>"
}

SCORING: 90-100=Exceptional, 80-89=Strong, 70-79=Good, 60-69=Average, 50-59=Below average, <50=Poor

Be specific, honest, and actionable. Do not inflate scores.
PROMPT;
    }
}

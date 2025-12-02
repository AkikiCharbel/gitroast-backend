<?php

declare(strict_types=1);

namespace App\Integrations\Anthropic;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class AnthropicConnector extends Connector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return 'https://api.anthropic.com/v1';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'anthropic-version' => '2023-06-01',
            'x-api-key' => config('services.anthropic.api_key', ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultConfig(): array
    {
        return [
            'timeout' => 120,
        ];
    }
}

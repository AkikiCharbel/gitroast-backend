<?php

declare(strict_types=1);

namespace App\Integrations\GitHub\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetUserEventsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $username
    ) {}

    public function resolveEndpoint(): string
    {
        return '/users/'.$this->username.'/events/public';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultQuery(): array
    {
        return [
            'per_page' => 100,
        ];
    }
}

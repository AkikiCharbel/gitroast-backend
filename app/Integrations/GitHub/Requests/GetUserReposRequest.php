<?php

declare(strict_types=1);

namespace App\Integrations\GitHub\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetUserReposRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $username,
        private readonly int $perPage = 30,
        private readonly string $sort = 'updated'
    ) {}

    public function resolveEndpoint(): string
    {
        return '/users/'.$this->username.'/repos';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultQuery(): array
    {
        return [
            'per_page' => $this->perPage,
            'sort' => $this->sort,
            'direction' => 'desc',
        ];
    }
}

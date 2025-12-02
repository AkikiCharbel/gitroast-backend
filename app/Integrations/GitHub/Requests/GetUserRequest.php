<?php

declare(strict_types=1);

namespace App\Integrations\GitHub\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetUserRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $username
    ) {}

    public function resolveEndpoint(): string
    {
        return '/users/'.$this->username;
    }
}

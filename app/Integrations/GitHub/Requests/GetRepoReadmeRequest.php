<?php

declare(strict_types=1);

namespace App\Integrations\GitHub\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetRepoReadmeRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $owner,
        private readonly string $repo
    ) {}

    public function resolveEndpoint(): string
    {
        return '/repos/'.$this->owner.'/'.$this->repo.'/readme';
    }
}

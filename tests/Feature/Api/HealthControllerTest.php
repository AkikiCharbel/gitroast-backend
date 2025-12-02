<?php

declare(strict_types=1);

describe('GET /api/health', function (): void {
    it('returns health status', function (): void {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => [
                    'database',
                    'redis',
                    'queue',
                ],
            ])
            ->assertJsonPath('status', 'healthy');
    });
});

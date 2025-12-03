<?php

declare(strict_types=1);

use App\Models\AnalysisRequest;

describe('AnalysisRequest model', function (): void {
    it('increments request count for IP', function (): void {
        $ip = '192.168.1.1';

        $request1 = AnalysisRequest::incrementForIp($ip);
        expect($request1->request_count)->toBe(1);

        $request2 = AnalysisRequest::incrementForIp($ip);
        expect($request2->request_count)->toBe(2);
    });

    it('gets count for IP within time window', function (): void {
        $ip = '192.168.1.2';

        AnalysisRequest::incrementForIp($ip);
        AnalysisRequest::incrementForIp($ip);

        expect(AnalysisRequest::getCountForIp($ip, 1))->toBe(2);
    });

    it('returns zero for unknown IP', function (): void {
        expect(AnalysisRequest::getCountForIp('unknown-ip', 1))->toBe(0);
    });

    it('prunes old records', function (): void {
        $request = AnalysisRequest::factory()->create([
            'last_request_at' => now()->subHours(48),
        ]);

        $deleted = AnalysisRequest::pruneOld(24);

        expect($deleted)->toBe(1);
        expect(AnalysisRequest::find($request->id))->toBeNull();
    });
});

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AnalysisRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $ip_address
 * @property int $request_count
 * @property \Illuminate\Support\Carbon $first_request_at
 * @property \Illuminate\Support\Carbon $last_request_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AnalysisRequest extends Model
{
    /** @use HasFactory<AnalysisRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'request_count',
        'first_request_at',
        'last_request_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'first_request_at' => 'datetime',
            'last_request_at' => 'datetime',
        ];
    }

    public static function incrementForIp(string $ipAddress): self
    {
        $request = self::firstOrNew(['ip_address' => $ipAddress]);

        if (! $request->exists) {
            $request->first_request_at = now();
            $request->request_count = 0;
        }

        $request->request_count++;
        $request->last_request_at = now();
        $request->save();

        return $request;
    }

    public static function getCountForIp(string $ipAddress, int $withinHours = 24): int
    {
        $request = self::where('ip_address', $ipAddress)
            ->where('last_request_at', '>=', now()->subHours($withinHours))
            ->first();

        return $request instanceof self ? $request->request_count : 0;
    }

    public static function pruneOld(int $olderThanHours = 24): int
    {
        $result = self::where('last_request_at', '<', now()->subHours($olderThanHours))
            ->delete();

        return is_int($result) ? $result : 0;
    }
}

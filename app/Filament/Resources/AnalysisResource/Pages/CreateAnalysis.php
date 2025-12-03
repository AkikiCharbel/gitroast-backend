<?php

declare(strict_types=1);

namespace App\Filament\Resources\AnalysisResource\Pages;

use App\Filament\Resources\AnalysisResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateAnalysis extends CreateRecord
{
    protected static string $resource = AnalysisResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = Str::uuid()->toString();

        return $data;
    }
}

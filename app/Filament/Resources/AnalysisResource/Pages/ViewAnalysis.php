<?php

declare(strict_types=1);

namespace App\Filament\Resources\AnalysisResource\Pages;

use App\Filament\Resources\AnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAnalysis extends ViewRecord
{
    protected static string $resource = AnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\AnalysisResource\Pages;

use App\Filament\Resources\AnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnalyses extends ListRecords
{
    protected static string $resource = AnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

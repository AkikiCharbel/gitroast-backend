<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\AnalysisStatus;
use App\Models\Analysis;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAnalysesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Analysis::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('github_username')
                    ->label('Username')
                    ->url(fn (Analysis $record): string => "https://github.com/{$record->github_username}", shouldOpenInNewTab: true),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => AnalysisStatus::PENDING->value,
                        'warning' => AnalysisStatus::PROCESSING->value,
                        'success' => AnalysisStatus::COMPLETED->value,
                        'danger' => AnalysisStatus::FAILED->value,
                    ]),
                Tables\Columns\TextColumn::make('overall_score')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 80 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\IconColumn::make('is_paid')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since(),
            ])
            ->paginated(false);
    }
}

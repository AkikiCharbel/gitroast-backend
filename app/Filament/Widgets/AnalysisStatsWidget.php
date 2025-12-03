<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\AnalysisStatus;
use App\Models\Analysis;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalysisStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalAnalyses = Analysis::count();
        $completedAnalyses = Analysis::where('status', AnalysisStatus::COMPLETED)->count();
        $paidAnalyses = Analysis::where('is_paid', true)->count();
        $averageScore = Analysis::where('status', AnalysisStatus::COMPLETED)
            ->whereNotNull('overall_score')
            ->avg('overall_score');

        return [
            Stat::make('Total Analyses', number_format($totalAnalyses))
                ->description('All time')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),

            Stat::make('Completed', number_format($completedAnalyses))
                ->description($totalAnalyses > 0 ? round(($completedAnalyses / $totalAnalyses) * 100, 1).'% success rate' : 'No analyses yet')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Paid Reports', number_format($paidAnalyses))
                ->description($completedAnalyses > 0 ? round(($paidAnalyses / $completedAnalyses) * 100, 1).'% conversion' : 'No conversions yet')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),

            Stat::make('Average Score', is_numeric($averageScore) ? number_format((float) $averageScore, 1) : 'N/A')
                ->description('Overall score')
                ->descriptionIcon('heroicon-m-star')
                ->color('info'),
        ];
    }
}

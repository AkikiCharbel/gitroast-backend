<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\AnalysisStatus;
use App\Filament\Resources\AnalysisResource\Pages;
use App\Models\Analysis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AnalysisResource extends Resource
{
    protected static ?string $model = Analysis::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Analysis';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Analysis Details')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled(),
                        Forms\Components\TextInput::make('github_username')
                            ->label('GitHub Username')
                            ->required()
                            ->maxLength(39),
                        Forms\Components\Select::make('status')
                            ->options(collect(AnalysisStatus::cases())->mapWithKeys(
                                fn (AnalysisStatus $status) => [$status->value => $status->label()]
                            ))
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Scores')
                    ->schema([
                        Forms\Components\TextInput::make('overall_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\TextInput::make('profile_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\TextInput::make('projects_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\TextInput::make('consistency_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\TextInput::make('technical_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\TextInput::make('community_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                    ])->columns(3),

                Forms\Components\Section::make('Payment')
                    ->schema([
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Is Paid'),
                        Forms\Components\TextInput::make('stripe_payment_id')
                            ->label('Stripe Payment ID'),
                        Forms\Components\DateTimePicker::make('paid_at'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->copyable()
                    ->limit(8),
                Tables\Columns\TextColumn::make('github_username')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Analysis $record): string => "https://github.com/{$record->github_username}", shouldOpenInNewTab: true),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => AnalysisStatus::PENDING->value,
                        'warning' => AnalysisStatus::PROCESSING->value,
                        'success' => AnalysisStatus::COMPLETED->value,
                        'danger' => AnalysisStatus::FAILED->value,
                    ]),
                Tables\Columns\TextColumn::make('overall_score')
                    ->sortable()
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 80 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\IconColumn::make('is_paid')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(AnalysisStatus::cases())->mapWithKeys(
                        fn (AnalysisStatus $status) => [$status->value => $status->label()]
                    )),
                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Payment Status')
                    ->placeholder('All')
                    ->trueLabel('Paid')
                    ->falseLabel('Unpaid'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Analysis Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('uuid')->label('UUID'),
                        Infolists\Components\TextEntry::make('github_username')
                            ->url(fn (Analysis $record): string => "https://github.com/{$record->github_username}", shouldOpenInNewTab: true),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (AnalysisStatus $state): string => $state->color()),
                        Infolists\Components\TextEntry::make('overall_score')
                            ->badge()
                            ->color(fn (?int $state): string => match (true) {
                                $state === null => 'gray',
                                $state >= 80 => 'success',
                                $state >= 60 => 'warning',
                                default => 'danger',
                            }),
                    ])->columns(4),

                Infolists\Components\Section::make('Category Scores')
                    ->schema([
                        Infolists\Components\TextEntry::make('profile_score')->label('Profile'),
                        Infolists\Components\TextEntry::make('projects_score')->label('Projects'),
                        Infolists\Components\TextEntry::make('consistency_score')->label('Consistency'),
                        Infolists\Components\TextEntry::make('technical_score')->label('Technical'),
                        Infolists\Components\TextEntry::make('community_score')->label('Community'),
                    ])->columns(5),

                Infolists\Components\Section::make('AI Analysis')
                    ->schema([
                        Infolists\Components\TextEntry::make('ai_analysis.summary')
                            ->label('Summary')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('ai_analysis.first_impression')
                            ->label('First Impression')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Infolists\Components\Section::make('Payment')
                    ->schema([
                        Infolists\Components\IconEntry::make('is_paid')->boolean(),
                        Infolists\Components\TextEntry::make('stripe_payment_id'),
                        Infolists\Components\TextEntry::make('paid_at')->dateTime(),
                    ])->columns(3),

                Infolists\Components\Section::make('Tracking')
                    ->schema([
                        Infolists\Components\TextEntry::make('ip_address'),
                        Infolists\Components\TextEntry::make('error_message'),
                        Infolists\Components\TextEntry::make('created_at')->dateTime(),
                        Infolists\Components\TextEntry::make('completed_at')->dateTime(),
                    ])->columns(4),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnalyses::route('/'),
            'create' => Pages\CreateAnalysis::route('/create'),
            'view' => Pages\ViewAnalysis::route('/{record}'),
            'edit' => Pages\EditAnalysis::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Analysis::where('status', AnalysisStatus::PENDING)->count();
    }
}

# Filament Admin Panel Documentation

Complete guide to the GitRoast admin panel built with Filament 3.

---

## Table of Contents

1. [Setup & Configuration](#setup--configuration)
2. [Panel Configuration](#panel-configuration)
3. [Resources](#resources)
4. [Widgets](#widgets)
5. [Custom Pages](#custom-pages)
6. [Customization](#customization)

---

## Setup & Configuration

### Installation

```bash
# Install Filament
composer require filament/filament:"^3.2"

# Install the panel
php artisan filament:install --panels

# Publish assets
php artisan filament:assets

# Create admin user
php artisan make:filament-user
```

### Directory Structure

```
app/Filament/
├── Pages/
│   └── Dashboard.php
├── Resources/
│   ├── AnalysisResource/
│   │   ├── Pages/
│   │   │   ├── CreateAnalysis.php
│   │   │   ├── EditAnalysis.php
│   │   │   ├── ListAnalyses.php
│   │   │   └── ViewAnalysis.php
│   │   └── AnalysisResource.php
│   ├── PaymentResource/
│   │   ├── Pages/
│   │   │   └── ListPayments.php
│   │   └── PaymentResource.php
│   └── UserResource/
│       ├── Pages/
│       │   ├── CreateUser.php
│       │   ├── EditUser.php
│       │   └── ListUsers.php
│       └── UserResource.php
└── Widgets/
    ├── AnalysisStatsWidget.php
    ├── ConversionRateWidget.php
    ├── RecentAnalysesWidget.php
    └── RevenueChartWidget.php
```

---

## Panel Configuration

### Admin Panel Provider

Create `app/Providers/Filament/AdminPanelProvider.php`:

```php
<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Indigo,
                'danger' => Color::Red,
                'gray' => Color::Slate,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->font('Inter')
            ->brandName('GitRoast Admin')
            ->brandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon.ico'))
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Analytics')
                    ->icon('heroicon-o-chart-bar'),
                NavigationGroup::make()
                    ->label('Management')
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\AnalysisStatsWidget::class,
                \App\Filament\Widgets\RevenueChartWidget::class,
                \App\Filament\Widgets\ConversionRateWidget::class,
                \App\Filament\Widgets\RecentAnalysesWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s');
    }
}
```

---

## Resources

### Analysis Resource

Create `app/Filament/Resources/AnalysisResource.php`:

```php
<?php

namespace App\Filament\Resources;

use App\Enums\AnalysisStatus;
use App\Enums\ScoreLevel;
use App\Filament\Resources\AnalysisResource\Pages;
use App\Models\Analysis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnalysisResource extends Resource
{
    protected static ?string $model = Analysis::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    
    protected static ?string $navigationGroup = 'Analytics';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'github_username';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::pending()->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Analysis Details')
                    ->schema([
                        Forms\Components\TextInput::make('github_username')
                            ->required()
                            ->maxLength(39)
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options(AnalysisStatus::class)
                            ->required(),
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Paid')
                            ->inline(false),
                    ])
                    ->columns(3),
                    
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
                    ])
                    ->columns(6)
                    ->collapsible(),
                    
                Forms\Components\Section::make('Error')
                    ->schema([
                        Forms\Components\Textarea::make('error_message')
                            ->rows(3),
                    ])
                    ->visible(fn ($record) => $record?->status === AnalysisStatus::FAILED)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('github_username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Username copied')
                    ->url(fn ($record) => "https://github.com/{$record->github_username}", true),
                    
                Tables\Columns\TextColumn::make('overall_score')
                    ->label('Score')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 80 => 'success',
                        $state >= 60 => 'warning',
                        $state >= 40 => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (AnalysisStatus $state) => $state->color()),
                    
                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Paid')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),
                    
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(AnalysisStatus::class),
                    
                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Payment Status')
                    ->placeholder('All')
                    ->trueLabel('Paid')
                    ->falseLabel('Free'),
                    
                Tables\Filters\Filter::make('high_score')
                    ->query(fn (Builder $query) => $query->where('overall_score', '>=', 80))
                    ->label('High Score (80+)'),
                    
                Tables\Filters\Filter::make('low_score')
                    ->query(fn (Builder $query) => $query->where('overall_score', '<', 50))
                    ->label('Low Score (<50)'),
                    
                Tables\Filters\Filter::make('created_today')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today()))
                    ->label('Created Today'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === AnalysisStatus::FAILED)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => AnalysisStatus::PENDING]);
                        \App\Jobs\ProcessAnalysisJob::dispatch($record);
                    }),
                Tables\Actions\Action::make('view_github')
                    ->label('GitHub')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => "https://github.com/{$record->github_username}", true)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('retry_failed')
                        ->label('Retry Failed')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === AnalysisStatus::FAILED) {
                                    $record->update(['status' => AnalysisStatus::PENDING]);
                                    \App\Jobs\ProcessAnalysisJob::dispatch($record);
                                }
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Overview')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('github_username')
                                    ->label('Username')
                                    ->url(fn ($record) => "https://github.com/{$record->github_username}", true)
                                    ->weight(FontWeight::Bold),
                                Infolists\Components\TextEntry::make('overall_score')
                                    ->label('Overall Score')
                                    ->badge()
                                    ->color(fn ($state) => match(true) {
                                        $state >= 80 => 'success',
                                        $state >= 60 => 'warning',
                                        default => 'danger',
                                    }),
                                Infolists\Components\TextEntry::make('status')
                                    ->badge(),
                                Infolists\Components\IconEntry::make('is_paid')
                                    ->label('Paid')
                                    ->boolean(),
                            ]),
                    ]),
                    
                Infolists\Components\Section::make('Category Scores')
                    ->schema([
                        Infolists\Components\Grid::make(5)
                            ->schema([
                                Infolists\Components\TextEntry::make('profile_score')
                                    ->label('Profile')
                                    ->suffix('/100'),
                                Infolists\Components\TextEntry::make('projects_score')
                                    ->label('Projects')
                                    ->suffix('/100'),
                                Infolists\Components\TextEntry::make('consistency_score')
                                    ->label('Consistency')
                                    ->suffix('/100'),
                                Infolists\Components\TextEntry::make('technical_score')
                                    ->label('Technical')
                                    ->suffix('/100'),
                                Infolists\Components\TextEntry::make('community_score')
                                    ->label('Community')
                                    ->suffix('/100'),
                            ]),
                    ])
                    ->collapsible(),
                    
                Infolists\Components\Section::make('AI Analysis')
                    ->schema([
                        Infolists\Components\TextEntry::make('ai_analysis.summary')
                            ->label('Summary')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('ai_analysis.first_impression')
                            ->label('First Impression')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('ai_analysis.recruiter_perspective')
                            ->label('Recruiter Perspective')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                    
                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('ip_address')
                                    ->label('IP Address'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('completed_at')
                                    ->label('Completed')
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
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

    public static function getGloballySearchableAttributes(): array
    {
        return ['github_username', 'uuid'];
    }
}
```

### Analysis Resource Pages

Create `app/Filament/Resources/AnalysisResource/Pages/ListAnalyses.php`:

```php
<?php

namespace App\Filament\Resources\AnalysisResource\Pages;

use App\Filament\Resources\AnalysisResource;
use App\Filament\Widgets\AnalysisStatsWidget;
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
    
    protected function getHeaderWidgets(): array
    {
        return [
            AnalysisStatsWidget::class,
        ];
    }
}
```

Create `app/Filament/Resources/AnalysisResource/Pages/ViewAnalysis.php`:

```php
<?php

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
```

### Payment Resource

Create `app/Filament/Resources/PaymentResource.php`:

```php
<?php

namespace App\Filament\Resources;

use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationGroup = 'Analytics';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\Select::make('analysis_id')
                            ->relationship('analysis', 'github_username')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('stripe_session_id')
                            ->required(),
                        Forms\Components\TextInput::make('stripe_payment_intent'),
                        Forms\Components\TextInput::make('amount_cents')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                            ])
                            ->default('USD'),
                        Forms\Components\Select::make('status')
                            ->options(PaymentStatus::class)
                            ->required(),
                        Forms\Components\TextInput::make('customer_email')
                            ->email(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('analysis.github_username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('formatted_amount')
                    ->label('Amount')
                    ->sortable(query: fn ($query, $direction) => 
                        $query->orderBy('amount_cents', $direction)
                    ),
                Tables\Columns\TextColumn::make('currency')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (PaymentStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('customer_email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PaymentStatus::class),
                Tables\Filters\Filter::make('created_today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('stripe')
                    ->label('View in Stripe')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => 
                        "https://dashboard.stripe.com/payments/{$record->stripe_payment_intent}"
                    , true)
                    ->visible(fn ($record) => $record->stripe_payment_intent),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}
```

### User Resource

Create `app/Filament/Resources/UserResource.php`:

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'Management';
    
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => 
                                filled($state) ? Hash::make($state) : null
                            )
                            ->required(fn ($livewire) => 
                                $livewire instanceof Pages\CreateUser
                            )
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_admin')
                            ->label('Admin Access')
                            ->helperText('Allow access to admin panel'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('Admin Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
```

---

## Widgets

### Analysis Stats Widget

Create `app/Filament/Widgets/AnalysisStatsWidget.php`:

```php
<?php

namespace App\Filament\Widgets;

use App\Enums\AnalysisStatus;
use App\Models\Analysis;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalysisStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = Analysis::whereDate('created_at', today());
        $thisWeek = Analysis::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
        
        return [
            Stat::make('Total Analyses', Analysis::count())
                ->description('All time')
                ->descriptionIcon('heroicon-m-document-magnifying-glass')
                ->color('primary'),
                
            Stat::make('Today', $today->count())
                ->description($today->paid()->count() . ' paid')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),
                
            Stat::make('This Week', $thisWeek->count())
                ->description($thisWeek->paid()->count() . ' paid')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
                
            Stat::make('Pending', Analysis::pending()->count())
                ->description('In queue')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Failed', Analysis::failed()->count())
                ->description('Need attention')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
                
            Stat::make('Avg Score', 
                number_format(Analysis::completed()->avg('overall_score') ?? 0, 1)
            )
                ->description('Completed analyses')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('gray'),
        ];
    }
}
```

### Revenue Chart Widget

Create `app/Filament/Widgets/RevenueChartWidget.php`:

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue (Last 30 Days)';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = Trend::model(Payment::class)
            ->between(
                start: now()->subDays(30),
                end: now(),
            )
            ->perDay()
            ->sum('amount_cents');

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate / 100),
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'borderColor' => 'rgb(99, 102, 241)',
                    'fill' => true,
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => 
                \Carbon\Carbon::parse($value->date)->format('M j')
            ),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value; }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
```

### Conversion Rate Widget

Create `app/Filament/Widgets/ConversionRateWidget.php`:

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Analysis;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class ConversionRateWidget extends ChartWidget
{
    protected static ?string $heading = 'Conversion Rate';
    
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $totalData = Trend::model(Analysis::class)
            ->between(start: now()->subDays(14), end: now())
            ->perDay()
            ->count();
            
        $paidData = Trend::query(
            Analysis::query()->where('is_paid', true)
        )
            ->between(start: now()->subDays(14), end: now())
            ->perDay()
            ->count();

        $rates = $totalData->map(function (TrendValue $total, $index) use ($paidData) {
            $paid = $paidData[$index]->aggregate ?? 0;
            return $total->aggregate > 0 
                ? round(($paid / $total->aggregate) * 100, 1) 
                : 0;
        });

        return [
            'datasets' => [
                [
                    'label' => 'Conversion %',
                    'data' => $rates,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
            ],
            'labels' => $totalData->map(fn (TrendValue $value) => 
                \Carbon\Carbon::parse($value->date)->format('M j')
            ),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
```

### Recent Analyses Widget

Create `app/Filament/Widgets/RecentAnalysesWidget.php`:

```php
<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AnalysisResource;
use App\Models\Analysis;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAnalysesWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Recent Analyses';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Analysis::query()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('github_username')
                    ->label('Username')
                    ->url(fn ($record) => 
                        AnalysisResource::getUrl('view', ['record' => $record])
                    ),
                Tables\Columns\TextColumn::make('overall_score')
                    ->label('Score')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 80 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Paid')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since(),
            ])
            ->paginated(false);
    }
}
```

---

## Custom Pages

### Custom Dashboard

Create `app/Filament/Pages/Dashboard.php`:

```php
<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.pages.dashboard';
    
    public function getColumns(): int | string | array
    {
        return 2;
    }
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\AnalysisStatsWidget::class,
            \App\Filament\Widgets\RevenueChartWidget::class,
            \App\Filament\Widgets\ConversionRateWidget::class,
            \App\Filament\Widgets\RecentAnalysesWidget::class,
        ];
    }
}
```

---

## Customization

### Custom Theme

Install Flowframe Trend for charts:

```bash
composer require flowframe/laravel-trend
```

### Global Search

The admin panel supports global search. Configure searchable attributes in each resource:

```php
public static function getGloballySearchableAttributes(): array
{
    return ['github_username', 'uuid'];
}
```

### Notifications

Enable database notifications in the panel config:

```php
->databaseNotifications()
->databaseNotificationsPolling('30s')
```

Send notifications from anywhere:

```php
use Filament\Notifications\Notification;

Notification::make()
    ->title('Analysis Completed')
    ->success()
    ->body("Analysis for {$username} scored {$score}")
    ->sendToDatabase($admin);
```

---

## Next Steps

1. Implement [Services](07-SERVICES.md)
2. Configure [Queue Jobs](08-QUEUES.md)
3. Set up [Testing](09-TESTING.md)

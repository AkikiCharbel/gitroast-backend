<?php

declare(strict_types=1);

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

    protected static ?string $navigationGroup = 'Payments';

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
                        Forms\Components\TextInput::make('paddle_transaction_id')
                            ->label('Transaction ID')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('paddle_subscription_id')
                            ->label('Subscription ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('amount_cents')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('currency')
                            ->required()
                            ->maxLength(3)
                            ->default('USD'),
                        Forms\Components\Select::make('status')
                            ->options(collect(PaymentStatus::cases())->mapWithKeys(
                                fn (PaymentStatus $status) => [$status->value => $status->label()]
                            ))
                            ->required(),
                        Forms\Components\TextInput::make('customer_email')
                            ->email()
                            ->maxLength(255),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('analysis.github_username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paddle_transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->limit(20)
                    ->copyable(),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => '$'.number_format($state / 100, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->badge(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => PaymentStatus::PENDING->value,
                        'success' => PaymentStatus::COMPLETED->value,
                        'danger' => PaymentStatus::FAILED->value,
                        'info' => PaymentStatus::REFUNDED->value,
                    ]),
                Tables\Columns\TextColumn::make('customer_email')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(
                        fn (PaymentStatus $status) => [$status->value => $status->label()]
                    )),
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
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', PaymentStatus::PENDING)->count();
    }
}

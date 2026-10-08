<?php

namespace App\Filament\Resources\Accounts\Tables;

use App\Models\Account;
use App\Services\AccountBalance;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'cash', 'bank' => 'success',
                        'staff' => 'info',
                        'income' => 'primary',
                        'expense' => 'warning',
                        'supplier' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('opening_balance')
                    ->money('PKR')
                    ->alignEnd()
                    ->toggleable(),
                TextColumn::make('balance')
                    ->label('Current balance')
                    ->alignEnd()
                    ->state(fn (Account $record): string => 'PKR '.number_format(app(AccountBalance::class)->balance($record), 2))
                    ->description(fn (Account $record): string => app(AccountBalance::class)->isDebitNormal($record) ? 'Dr normal' : 'Cr normal'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(collect(Account::TYPES)
                        ->mapWithKeys(fn (string $t): array => [$t => ucfirst($t)])
                        ->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                ]),
            ]);
    }
}

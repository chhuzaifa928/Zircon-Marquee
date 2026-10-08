<?php

namespace App\Filament\Resources\Vouchers\Tables;

use App\Models\Voucher;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('voucher_no')
                    ->label('Voucher #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state)
                    ->color(fn (string $state): string => match ($state) {
                        'RV' => 'success',
                        'EV' => 'warning',
                        'PV' => 'danger',
                        'JV' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('category')
                    ->toggleable(),
                TextColumn::make('debitAccount.name')
                    ->label('Debit')
                    ->description(fn (Voucher $r): ?string => $r->debitAccount?->code),
                TextColumn::make('creditAccount.name')
                    ->label('Credit')
                    ->description(fn (Voucher $r): ?string => $r->creditAccount?->code),
                TextColumn::make('amount')
                    ->money('PKR')
                    ->alignEnd()
                    ->sortable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('PKR')),
                TextColumn::make('source_type')
                    ->label('Source')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : 'Manual')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('createdBy.name')
                    ->label('Posted by')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(Voucher::TYPE_LABELS),
                SelectFilter::make('debit_account_id')
                    ->label('Debit account')
                    ->relationship('debitAccount', 'name')
                    ->searchable(),
                Filter::make('manual')
                    ->label('Manual entries only')
                    ->query(fn (Builder $query): Builder => $query->whereNull('source_type')),
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

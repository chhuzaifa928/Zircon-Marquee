<?php

namespace App\Filament\Resources\PaymentSlips\Tables;

use App\Models\PaymentSlip;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentSlipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('slip_no')
                    ->label('Slip #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('booking.booking_no')
                    ->label('Booking')
                    ->description(fn (PaymentSlip $r): ?string => $r->booking?->customer?->name)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account.name')
                    ->label('Received into'),
                TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? ucwords(str_replace('_', ' ', $state)) : '—'),
                TextColumn::make('reference')
                    ->toggleable(),
                TextColumn::make('amount')
                    ->money('PKR')
                    ->sortable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('PKR')),
                TextColumn::make('createdBy.name')
                    ->label('Created by')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('account_id')
                    ->label('Account')
                    ->relationship('account', 'name'),
                SelectFilter::make('method')
                    ->options(collect(PaymentSlip::METHODS)
                        ->mapWithKeys(fn (string $m): array => [$m => ucwords(str_replace('_', ' ', $m))])
                        ->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
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

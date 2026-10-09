<?php

namespace App\Filament\Resources\Assets\Tables;

use App\Models\Asset;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category')->searchable()->toggleable(),
                TextColumn::make('quantity')->alignEnd()->sortable(),
                TextColumn::make('purchase_cost')->money('PKR')->alignEnd()->toggleable(),
                TextColumn::make('condition')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '—')
                    ->color(fn (?string $state): string => match ($state) {
                        'new', 'good' => 'success',
                        'fair' => 'warning',
                        'poor' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'in_use' => 'info',
                        'retired' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(Asset::STATUSES)->mapWithKeys(fn ($s) => [$s => ucwords(str_replace('_', ' ', $s))])->all()),
                SelectFilter::make('condition')
                    ->options(collect(Asset::CONDITIONS)->mapWithKeys(fn ($c) => [$c => ucfirst($c)])->all()),
            ])
            ->recordActions([
                ActionGroup::make([ViewAction::make(), EditAction::make()]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                ]),
            ]);
    }
}

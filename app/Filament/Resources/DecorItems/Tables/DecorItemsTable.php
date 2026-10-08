<?php

namespace App\Filament\Resources\DecorItems\Tables;

use App\Models\DecorItem;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DecorItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('quantity')->alignEnd()->sortable(),
                TextColumn::make('rate')->money('PKR')->alignEnd()->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $s): string => ucwords(str_replace('_', ' ', $s)))
                    ->color(fn (string $s): string => match ($s) {
                        'available' => 'success',
                        'in_use' => 'info',
                        'retired' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(DecorItem::STATUSES)->mapWithKeys(fn ($s) => [$s => ucwords(str_replace('_', ' ', $s))])->all()),
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

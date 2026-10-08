<?php

namespace App\Filament\Resources\Vendors\Tables;

use App\Models\Vendor;
use App\Services\AccountBalance;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('company')->searchable()->toggleable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('account.code')->label('Account')->toggleable(),
                TextColumn::make('balance')
                    ->label('Balance owed')
                    ->alignEnd()
                    ->state(fn (Vendor $r): string => $r->account
                        ? 'PKR '.number_format(app(AccountBalance::class)->balance($r->account), 2)
                        : '—'),
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

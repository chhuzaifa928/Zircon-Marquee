<?php

namespace App\Filament\Resources\EventCosts;

use App\Filament\Resources\EventCosts\Pages\CreateEventCost;
use App\Filament\Resources\EventCosts\Pages\EditEventCost;
use App\Filament\Resources\EventCosts\Pages\ListEventCosts;
use App\Filament\Resources\EventCosts\Schemas\EventCostForm;
use App\Filament\Resources\EventCosts\Tables\EventCostsTable;
use App\Models\EventCost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class EventCostResource extends Resource
{
    protected static ?string $model = EventCost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Event cost';

    public static function form(Schema $schema): Schema
    {
        return EventCostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventCostsTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accounts']) ?? false;
    }

    /** Accounts record event costs (after full payment, §10.2). */
    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accounts']) ?? false;
    }

    /** Accounting entries are immutable to their authors (§9.5). */
    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventCosts::route('/'),
            'create' => CreateEventCost::route('/create'),
            'edit' => EditEventCost::route('/{record}/edit'),
        ];
    }
}

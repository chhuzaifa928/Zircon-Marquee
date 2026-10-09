<?php

namespace App\Filament\Resources\DecorItems;

use App\Filament\Resources\DecorItems\Pages\CreateDecorItem;
use App\Filament\Resources\DecorItems\Pages\EditDecorItem;
use App\Filament\Resources\DecorItems\Pages\ListDecorItems;
use App\Filament\Resources\DecorItems\Schemas\DecorItemForm;
use App\Filament\Resources\DecorItems\Tables\DecorItemsTable;
use App\Models\DecorItem;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DecorItemResource extends Resource
{
    protected static ?string $model = DecorItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Decor';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return DecorItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DecorItemsTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->inAnyRole(User::DECOR) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->inAnyRole(User::DECOR) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDecorItems::route('/'),
            'create' => CreateDecorItem::route('/create'),
            'edit' => EditDecorItem::route('/{record}/edit'),
        ];
    }
}

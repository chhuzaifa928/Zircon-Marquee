<?php

namespace App\Filament\Resources\InventoryItems\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Inventory item')
                    ->columns(2)
                    ->components([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('category')
                            ->maxLength(255),
                        TextInput::make('unit')
                            ->label('Unit of measure')
                            ->placeholder('kg, ltr, pcs, pack…')
                            ->maxLength(50),
                        TextInput::make('reorder_level')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Flagged low when quantity on hand reaches this level.'),
                        TextInput::make('unit_cost')
                            ->numeric()
                            ->prefix('PKR')
                            ->default(0)
                            ->minValue(0),
                        TextInput::make('qty_on_hand')
                            ->label('Quantity on hand')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Maintained automatically from stock movements.')
                            ->visibleOn('edit'),
                        Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);
    }
}

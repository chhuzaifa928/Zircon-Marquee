<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use App\Models\InventoryItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Stock movement')
                    ->columns(2)
                    ->components([
                        Select::make('inventory_item_id')
                            ->label('Item')
                            ->relationship('inventoryItem', 'name', fn ($query) => $query->where('is_active', true))
                            ->getOptionLabelFromRecordUsing(fn (InventoryItem $r): string => "{$r->code} — {$r->name}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        Select::make('type')
                            ->options([
                                'in' => 'In (received / purchased)',
                                'out' => 'Out (consumed / used)',
                                'adjustment' => 'Adjustment (correction, +/−)',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->helperText('For an adjustment, use a negative value to reduce stock.'),
                        TextInput::make('unit_cost')
                            ->numeric()
                            ->prefix('PKR'),
                        DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d M Y'),
                        Select::make('booking_id')
                            ->label('Booking (optional)')
                            ->relationship('booking', 'booking_no')
                            ->searchable()
                            ->preload(),
                        TextInput::make('reference')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

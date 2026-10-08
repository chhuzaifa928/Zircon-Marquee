<?php

namespace App\Filament\Resources\InventoryItems\RelationManagers;

use App\Models\StockMovement;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Stock movements for an inventory item (SRS §11.2). Recording a movement
 * recomputes the item's quantity on hand (via the model's lifecycle hooks).
 */
class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Stock movements';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
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
                TextInput::make('reference')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->date('d M Y')->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $s): string => ucfirst($s))
                    ->color(fn (string $s): string => match ($s) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('quantity')->alignEnd()->sortable(),
                TextColumn::make('unit_cost')->money('PKR')->alignEnd()->toggleable(),
                TextColumn::make('reference')->toggleable(),
                TextColumn::make('booking.booking_no')->label('Booking')->toggleable(),
                TextColumn::make('createdBy.name')->label('By')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Record movement')
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole(['super_admin', 'admin', 'inventory']) ?? false),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}

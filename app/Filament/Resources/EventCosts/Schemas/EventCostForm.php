<?php

namespace App\Filament\Resources\EventCosts\Schemas;

use App\Models\Account;
use App\Models\Booking;
use App\Models\InventoryItem;
use App\Models\Vendor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class EventCostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event cost')
                    ->description('Costs may be recorded only for a fully-paid event (SRS §10.2).')
                    ->columns(2)
                    ->components([
                        Select::make('booking_id')
                            ->label('Booking (fully paid)')
                            ->relationship('booking', 'booking_no', fn ($query) => $query->where('status', 'paid'))
                            ->getOptionLabelFromRecordUsing(fn (Booking $r): string => "{$r->booking_no} — {$r->customer?->name}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        Select::make('cost_type')
                            ->options(['vendor' => 'Vendor service', 'inventory' => 'Inventory consumed', 'misc' => 'Miscellaneous'])
                            ->required()
                            ->live()
                            ->native(false),

                        DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d M Y'),

                        // Vendor cost
                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Vendor $r): string => $r->company ? "{$r->name} ({$r->company})" : $r->name)
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('cost_type') === 'vendor')
                            ->required(fn (Get $get): bool => $get('cost_type') === 'vendor'),

                        // Inventory cost
                        Select::make('inventory_item_id')
                            ->label('Inventory item')
                            ->relationship('inventoryItem', 'name', fn ($query) => $query->where('is_active', true))
                            ->getOptionLabelFromRecordUsing(fn (InventoryItem $r): string => "{$r->code} — {$r->name} (on hand {$r->qty_on_hand})")
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn (Get $get): bool => $get('cost_type') === 'inventory')
                            ->required(fn (Get $get): bool => $get('cost_type') === 'inventory')
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recomputeInventoryAmount($get, $set)),

                        TextInput::make('quantity')
                            ->label('Quantity consumed')
                            ->numeric()
                            ->minValue(0.01)
                            ->live(onBlur: true)
                            ->visible(fn (Get $get): bool => $get('cost_type') === 'inventory')
                            ->required(fn (Get $get): bool => $get('cost_type') === 'inventory')
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recomputeInventoryAmount($get, $set)),

                        // Expense head (vendor + misc)
                        Select::make('expense_account_id')
                            ->label('Expense head (debit)')
                            ->options(fn () => Account::where('type', 'expense')->where('is_active', true)->orderBy('code')
                                ->get()->mapWithKeys(fn (Account $a) => [$a->id => "{$a->code} — {$a->name}"])->all())
                            ->searchable()
                            ->visible(fn (Get $get): bool => in_array($get('cost_type'), ['vendor', 'misc'], true))
                            ->required(fn (Get $get): bool => in_array($get('cost_type'), ['vendor', 'misc'], true)),

                        // Paid-from (misc only)
                        Select::make('paid_from_account_id')
                            ->label('Paid from (cash/bank)')
                            ->options(fn () => Account::whereIn('type', ['cash', 'bank'])->where('is_active', true)->orderBy('code')
                                ->get()->mapWithKeys(fn (Account $a) => [$a->id => "{$a->code} — {$a->name}"])->all())
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('cost_type') === 'misc')
                            ->required(fn (Get $get): bool => $get('cost_type') === 'misc'),

                        TextInput::make('amount')
                            ->numeric()
                            ->prefix('PKR')
                            ->required()
                            ->disabled(fn (Get $get): bool => $get('cost_type') === 'inventory')
                            ->dehydrated()
                            ->helperText(fn (Get $get): ?string => $get('cost_type') === 'inventory' ? 'Auto: quantity × item unit cost' : null),

                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function recomputeInventoryAmount(Get $get, Set $set): void
    {
        $item = InventoryItem::find($get('inventory_item_id'));
        $qty = (float) $get('quantity');

        if ($item !== null) {
            $set('amount', round($qty * (float) $item->unit_cost, 2));
        }
    }
}

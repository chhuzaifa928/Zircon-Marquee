<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Models\Asset;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asset')
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
                            ->placeholder('Chairs, Tables, Crockery…')
                            ->maxLength(255),
                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->required(),
                        DatePicker::make('purchase_date')
                            ->native(false)
                            ->displayFormat('d M Y'),
                        TextInput::make('purchase_cost')
                            ->numeric()
                            ->prefix('PKR'),
                        Select::make('condition')
                            ->options(collect(Asset::CONDITIONS)
                                ->mapWithKeys(fn (string $c): array => [$c => ucfirst($c)])
                                ->all())
                            ->native(false),
                        Select::make('status')
                            ->options(collect(Asset::STATUSES)
                                ->mapWithKeys(fn (string $s): array => [$s => ucwords(str_replace('_', ' ', $s))])
                                ->all())
                            ->default('available')
                            ->required()
                            ->native(false),
                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

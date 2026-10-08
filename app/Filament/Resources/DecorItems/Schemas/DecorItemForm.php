<?php

namespace App\Filament\Resources\DecorItems\Schemas;

use App\Models\DecorItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DecorItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Decor item')
                    ->columns(2)
                    ->components([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->required(),
                        TextInput::make('rate')
                            ->numeric()
                            ->prefix('PKR'),
                        Select::make('status')
                            ->options(collect(DecorItem::STATUSES)
                                ->mapWithKeys(fn (string $s): array => [$s => ucwords(str_replace('_', ' ', $s))])
                                ->all())
                            ->default('available')
                            ->required()
                            ->native(false),
                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

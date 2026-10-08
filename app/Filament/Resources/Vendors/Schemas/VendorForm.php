<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vendor / supplier')
                    ->description('A linked supplier payable account is created automatically on first save.')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('company')
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('opening_balance')
                            ->label('Opening balance (payable)')
                            ->numeric()
                            ->prefix('PKR')
                            ->default(0)
                            ->helperText('Seeded onto the linked account; editable there afterwards.')
                            ->disabledOn('edit'),
                        Textarea::make('address')
                            ->rows(2),
                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

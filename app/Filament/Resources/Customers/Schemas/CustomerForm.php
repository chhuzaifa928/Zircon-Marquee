<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Host details')
                    ->description('Client code is assigned automatically (CUST-0001).')
                    ->columns(2)
                    ->components([
                        TextInput::make('code')
                            ->label('Client code')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->visibleOn('edit'),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('care_of')
                            ->label('Care of (c/o)')
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('cnic')
                            ->label('CNIC')
                            ->maxLength(20),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

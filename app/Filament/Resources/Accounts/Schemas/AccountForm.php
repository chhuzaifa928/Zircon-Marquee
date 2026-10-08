<?php

namespace App\Filament\Resources\Accounts\Schemas;

use App\Models\Account;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account head')
                    ->columns(2)
                    ->components([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->helperText('Chart-of-accounts code, e.g. 1000.'),
                        Select::make('type')
                            ->options(collect(Account::TYPES)
                                ->mapWithKeys(fn (string $t): array => [$t => ucfirst($t)])
                                ->all())
                            ->required()
                            ->native(false),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('opening_balance')
                            ->numeric()
                            ->prefix('PKR')
                            ->default(0)
                            ->required()
                            ->helperText('On the account\'s normal side (debit for cash/bank/staff/expense, credit for income/supplier).'),
                        Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);
    }
}

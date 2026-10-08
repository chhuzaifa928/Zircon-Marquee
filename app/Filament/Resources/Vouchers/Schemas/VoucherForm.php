<?php

namespace App\Filament\Resources\Vouchers\Schemas;

use App\Models\Account;
use App\Models\Voucher;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        $accountOptions = fn () => Account::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Account $a): array => [$a->id => "{$a->code} — {$a->name} (".ucfirst($a->type).')'])
            ->all();

        return $schema
            ->components([
                Section::make('Voucher')
                    ->description('Double-entry: one account is debited and another credited for the same amount (§9.2). Transfers between accounts are ordinary two-sided vouchers.')
                    ->columns(2)
                    ->components([
                        TextInput::make('voucher_no')
                            ->label('Voucher #')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->visibleOn('edit'),

                        Select::make('type')
                            ->options(Voucher::TYPE_LABELS)
                            ->required()
                            ->native(false)
                            ->helperText('RV money in · EV expense incurred · PV payment out'),

                        DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d M Y'),

                        TextInput::make('category')
                            ->maxLength(255)
                            ->helperText('e.g. Banquet Advance, Kitchen Daily Expense, Utility Bills'),

                        Select::make('debit_account_id')
                            ->label('Debit account')
                            ->options($accountOptions)
                            ->searchable()
                            ->required()
                            ->different('credit_account_id'),

                        Select::make('credit_account_id')
                            ->label('Credit account')
                            ->options($accountOptions)
                            ->searchable()
                            ->required()
                            ->different('debit_account_id'),

                        TextInput::make('amount')
                            ->numeric()
                            ->prefix('PKR')
                            ->minValue(0.01)
                            ->required(),

                        Textarea::make('remark')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

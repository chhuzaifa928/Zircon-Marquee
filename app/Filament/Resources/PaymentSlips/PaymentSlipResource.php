<?php

namespace App\Filament\Resources\PaymentSlips;

use App\Filament\Resources\PaymentSlips\Pages\CreatePaymentSlip;
use App\Filament\Resources\PaymentSlips\Pages\EditPaymentSlip;
use App\Filament\Resources\PaymentSlips\Pages\ListPaymentSlips;
use App\Filament\Resources\PaymentSlips\Schemas\PaymentSlipForm;
use App\Filament\Resources\PaymentSlips\Tables\PaymentSlipsTable;
use App\Models\PaymentSlip;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PaymentSlipResource extends Resource
{
    protected static ?string $model = PaymentSlip::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'slip_no';

    protected static ?string $modelLabel = 'Payment slip';

    public static function form(Schema $schema): Schema
    {
        return PaymentSlipForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentSlipsTable::configure($table);
    }

    /** Accounts, Admin and Super Admin record payments (SRS permission matrix). */
    public static function canCreate(): bool
    {
        return auth()->user()?->inAnyRole(User::ACCOUNTING) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->inAnyRole(User::ACCOUNTING) ?? false;
    }

    /** Only the Super Admin may change a posted slip (SRS §9.5, business rule 9). */
    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentSlips::route('/'),
            'create' => CreatePaymentSlip::route('/create'),
            'edit' => EditPaymentSlip::route('/{record}/edit'),
        ];
    }
}

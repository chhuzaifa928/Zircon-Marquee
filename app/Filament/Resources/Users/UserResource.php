<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * User management (SRS §4.2). Admin and Super Admin add/edit/deactivate users
 * and assign roles. Safeguards: a user cannot delete their own account, only a
 * Super Admin may edit/delete another Super Admin, and the last Super Admin
 * can never be removed.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->inAnyRole(User::ADMINS) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->inAnyRole(User::ADMINS) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $actor = auth()->user();

        if (! ($actor?->inAnyRole(User::ADMINS) ?? false)) {
            return false;
        }

        // Only a Super Admin may edit another Super Admin.
        if ($record->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    public static function canDelete(Model $record): bool
    {
        $actor = auth()->user();

        if (! ($actor?->inAnyRole(User::ADMINS) ?? false)) {
            return false;
        }

        // Never delete yourself.
        if ($actor->is($record)) {
            return false;
        }

        // Only a Super Admin may delete another Super Admin.
        if ($record->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            return false;
        }

        // Never remove the last Super Admin.
        if ($record->isSuperAdmin() && static::superAdminCount() <= 1) {
            return false;
        }

        return true;
    }

    public static function superAdminCount(): int
    {
        return User::role('super_admin')->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}

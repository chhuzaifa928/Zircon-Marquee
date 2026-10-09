<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->rule(Password::default())
                            ->autocomplete('new-password')
                            // Required on create; on edit only updates when filled.
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Min 12 chars with upper, lower, number and symbol.'),
                        Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Inactive users cannot log in.'),
                        Select::make('roles')
                            ->relationship(
                                'roles',
                                'name',
                                // Only a Super Admin may grant the super_admin role.
                                fn ($query) => $query->when(
                                    ! (auth()->user()?->isSuperAdmin() ?? false),
                                    fn ($q) => $q->where('name', '!=', 'super_admin'),
                                ),
                            )
                            ->multiple()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

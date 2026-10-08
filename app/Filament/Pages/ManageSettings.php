<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Company settings, GST rate and the system-lock kill-switch (SRS §4.3–4.5).
 * Admin and Super Admin may edit; only the Super Admin may toggle the lock.
 */
class ManageSettings extends Page
{
    protected string $view = 'filament.pages.manage-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Settings';

    /** @var array<string,mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Setting::current()->attributesToArray());
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        $isSuper = auth()->user()?->isSuperAdmin() ?? false;

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Company')
                    ->description('Appears on all printed documents.')
                    ->columns(2)
                    ->components([
                        TextInput::make('company_name')->required()->maxLength(255),
                        TextInput::make('company_phone')->tel()->maxLength(50),
                        TextInput::make('company_email')->email()->maxLength(255),
                        TextInput::make('currency')->default('PKR')->maxLength(10),
                        Textarea::make('company_address')->rows(2)->columnSpanFull(),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->directory('branding')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ]),

                Section::make('Taxation')
                    ->components([
                        TextInput::make('gst_rate')
                            ->label('GST rate')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->helperText('Applied to new bookings; existing bookings keep their snapshot.'),
                    ]),

                Section::make('System')
                    ->components([
                        Toggle::make('system_locked')
                            ->label('Lock the system (maintenance kill-switch)')
                            ->helperText($isSuper
                                ? 'When on, only the Super Admin can log in; everyone else is denied access.'
                                : 'Only the Super Admin can change this.')
                            ->disabled(! $isSuper)
                            ->dehydrated($isSuper),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Defence in depth: only the Super Admin may change the lock.
        if (! (auth()->user()?->isSuperAdmin() ?? false)) {
            unset($data['system_locked']);
        }

        Setting::current()->update($data);

        Notification::make()->success()->title('Settings saved')->send();
    }
}

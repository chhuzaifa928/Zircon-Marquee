<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /*
     * Role groups — the single source of the module-access matrix (SRS §3).
     * super_admin is omitted here because Gate::before grants it everything.
     */
    public const ADMINS = ['super_admin', 'admin'];

    public const ACCOUNTING = ['super_admin', 'admin', 'accounts'];

    public const INVENTORY = ['super_admin', 'admin', 'inventory'];

    public const DECOR = ['super_admin', 'admin', 'decor'];

    /** View bookings/customers: sales + accounts + admins. */
    public const BOOKING_VIEW = ['super_admin', 'admin', 'sales', 'accounts'];

    /** Create/edit bookings/customers: sales + admins (accounts view+confirm only). */
    public const BOOKING_MANAGE = ['super_admin', 'admin', 'sales'];

    /** View vendors: admins + accounts (who pay them). */
    public const VENDOR_VIEW = ['super_admin', 'admin', 'accounts'];

    /** Convenience wrapper over spatie hasAnyRole for the groups above. */
    public function inAnyRole(array $roles): bool
    {
        return $this->hasAnyRole($roles);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Super admin (Khan Group) — bypasses all authorization via Gate::before.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Who may reach the Filament admin panel. Inactive users are always denied;
     * while the system is locked (settings.system_locked) only the Super Admin
     * may log in (SRS §4.4).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (Setting::current()->system_locked && ! $this->isSuperAdmin()) {
            return false;
        }

        return true;
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    private function user(string $role): User
    {
        $user = User::create(['name' => ucfirst($role), 'email' => $role.'@zircon.test', 'password' => bcrypt('secret'), 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function panel(): Panel
    {
        return Filament::getPanel('admin');
    }

    public function test_admin_can_access_settings_but_sales_cannot(): void
    {
        $this->actingAs($this->user('admin'));
        Filament::setCurrentPanel($this->panel());
        $this->assertTrue(ManageSettings::canAccess());

        $this->actingAs($this->user('sales'));
        $this->assertFalse(ManageSettings::canAccess());
    }

    public function test_admin_can_change_gst_rate(): void
    {
        $this->actingAs($this->user('admin'));
        Filament::setCurrentPanel($this->panel());

        Livewire::test(ManageSettings::class)
            ->fillForm(['gst_rate' => 17])
            ->call('save');

        $this->assertSame('17.00', Setting::current()->gst_rate);
    }

    public function test_super_admin_can_toggle_system_lock(): void
    {
        $this->actingAs($this->user('super_admin'));
        Filament::setCurrentPanel($this->panel());

        Livewire::test(ManageSettings::class)
            ->fillForm(['system_locked' => true])
            ->call('save');

        $this->assertTrue((bool) Setting::current()->system_locked);
    }

    public function test_admin_cannot_change_system_lock(): void
    {
        Setting::current()->update(['system_locked' => false]);
        $this->actingAs($this->user('admin'));
        Filament::setCurrentPanel($this->panel());

        // Even if the field is submitted, a non-super-admin save ignores it.
        Livewire::test(ManageSettings::class)
            ->fillForm(['system_locked' => true])
            ->call('save');

        $this->assertFalse((bool) Setting::current()->system_locked);
    }

    public function test_system_lock_blocks_non_super_admin_from_the_panel(): void
    {
        Setting::current()->update(['system_locked' => true]);

        $this->assertFalse($this->user('accounts')->canAccessPanel($this->panel()));
        $this->assertTrue($this->user('super_admin')->canAccessPanel($this->panel()));
    }

    public function test_inactive_user_is_always_denied(): void
    {
        $user = $this->user('super_admin');
        $user->update(['is_active' => false]);

        $this->assertFalse($user->canAccessPanel($this->panel()));
    }

    public function test_unlocked_system_allows_active_users(): void
    {
        Setting::current()->update(['system_locked' => false]);

        $this->assertTrue($this->user('accounts')->canAccessPanel($this->panel()));
    }
}

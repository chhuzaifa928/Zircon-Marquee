<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    private function make(string $role, string $email): User
    {
        $user = User::create(['name' => ucfirst($role), 'email' => $email, 'password' => bcrypt('Secret-Passw0rd!'), 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function actAs(User $user): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_only_admins_manage_users(): void
    {
        $this->actAs($this->make('sales', 'sales@z.test'));
        $this->assertFalse(UserResource::canViewAny());

        $this->actAs($this->make('admin', 'admin@z.test'));
        $this->assertTrue(UserResource::canViewAny());
    }

    public function test_admin_can_create_a_user_with_roles(): void
    {
        $this->actAs($this->make('admin', 'admin@z.test'));

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New Clerk',
                'email' => 'clerk@z.test',
                'password' => 'Str0ng-Password!!',
                'is_active' => true,
                'roles' => [\Spatie\Permission\Models\Role::findByName('accounts')->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::firstWhere('email', 'clerk@z.test');
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('accounts'));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Str0ng-Password!!', $user->password));
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->actAs($this->make('admin', 'admin@z.test'));

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Weak',
                'email' => 'weak@z.test',
                'password' => 'short',
                'roles' => ['sales'],
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);
    }

    public function test_a_user_cannot_delete_themselves(): void
    {
        $admin = $this->make('admin', 'admin@z.test');
        $this->actAs($admin);

        $this->assertFalse(UserResource::canDelete($admin));
    }

    public function test_admin_cannot_edit_or_delete_a_super_admin(): void
    {
        $super = $this->make('super_admin', 'super@z.test');
        $this->make('super_admin', 'super2@z.test'); // ensure not the last
        $admin = $this->make('admin', 'admin@z.test');
        $this->actAs($admin);

        $this->assertFalse(UserResource::canEdit($super));
        $this->assertFalse(UserResource::canDelete($super));
    }

    public function test_last_super_admin_cannot_be_deleted(): void
    {
        $super = $this->make('super_admin', 'super@z.test');
        $other = $this->make('super_admin', 'super2@z.test');
        $this->actAs($super);

        // Two super admins: the other is deletable.
        $this->assertTrue(UserResource::canDelete($other));

        $other->delete();
        // Now only one remains → protected.
        $this->assertFalse(UserResource::canDelete($super->fresh()));
    }

    public function test_security_headers_present_on_web_responses(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}

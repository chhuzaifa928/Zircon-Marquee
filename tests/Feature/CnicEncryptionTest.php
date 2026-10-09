<?php

namespace Tests\Feature;

use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CnicEncryptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_cnic_is_stored_encrypted_but_reads_back_plain(): void
    {
        $customer = Customer::create(['name' => 'Enc Host', 'cnic' => '37405-1234567-1']);

        $raw = DB::table('customers')->where('id', $customer->id)->value('cnic');

        $this->assertNotSame('37405-1234567-1', $raw);          // ciphertext at rest
        $this->assertStringNotContainsString('1234567', $raw);  // plaintext not present
        $this->assertSame('37405-1234567-1', $customer->fresh()->cnic); // decrypts on read
    }

    public function test_blind_index_is_set_and_normalised(): void
    {
        $customer = Customer::create(['name' => 'Idx Host', 'cnic' => '37405-7654321-9']);

        $index = DB::table('customers')->where('id', $customer->id)->value('cnic_index');
        $this->assertNotNull($index);

        // Dashed and digits-only forms produce the same index.
        $this->assertSame(
            Customer::blindIndex('37405-7654321-9'),
            Customer::blindIndex('3740576543219'),
        );
        $this->assertSame($index, Customer::blindIndex('3740576543219'));
    }

    public function test_exact_lookup_via_scope(): void
    {
        $target = Customer::create(['name' => 'Target', 'cnic' => '11111-2222222-3']);
        Customer::create(['name' => 'Other', 'cnic' => '99999-8888888-7']);

        $this->assertTrue(Customer::whereCnic('11111-2222222-3')->get()->contains($target));
        $this->assertTrue(Customer::whereCnic('1111122222223')->get()->contains($target)); // dashless
        $this->assertFalse(Customer::whereCnic('00000-0000000-0')->exists());
    }

    public function test_blank_cnic_has_no_index(): void
    {
        $customer = Customer::create(['name' => 'No CNIC']);

        $this->assertNull($customer->cnic);
        $this->assertNull(DB::table('customers')->where('id', $customer->id)->value('cnic_index'));
    }

    public function test_updating_cnic_refreshes_the_index(): void
    {
        $customer = Customer::create(['name' => 'Changer', 'cnic' => '11111-1111111-1']);
        $first = DB::table('customers')->where('id', $customer->id)->value('cnic_index');

        $customer->update(['cnic' => '22222-2222222-2']);
        $second = DB::table('customers')->where('id', $customer->id)->value('cnic_index');

        $this->assertNotSame($first, $second);
        $this->assertSame(Customer::blindIndex('22222-2222222-2'), $second);
    }

    public function test_customers_table_search_finds_by_exact_cnic(): void
    {
        $user = User::create(['name' => 'Sales', 'email' => 'sales@z.test', 'password' => bcrypt('Secret-Passw0rd!'), 'is_active' => true]);
        $user->assignRole('sales');
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $target = Customer::create(['name' => 'Searchable', 'cnic' => '37405-5556667-8']);
        $other = Customer::create(['name' => 'Hidden', 'cnic' => '37405-0001112-3']);

        Livewire::test(ListCustomers::class)
            ->searchTable('37405-5556667-8')
            ->assertCanSeeTableRecords([$target])
            ->assertCanNotSeeTableRecords([$other]);
    }
}

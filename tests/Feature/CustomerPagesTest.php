<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_index_is_displayed_with_expected_payload(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer($user, [
            'company_name' => 'Acme Corp',
            'company_id' => '12345678',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('customers.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Index')
                ->has('customers.data', 1)
                ->has('customers.links')
                ->where('customers.data.0.id', $customer->id)
                ->where('customers.data.0.company_name', $customer->company_name)
                ->has('filters')
            );
    }

    public function test_customers_index_only_lists_authenticated_user_customers(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownCustomer = $this->createCustomer($user, ['company_name' => 'Own Customer']);
        $this->createCustomer($otherUser, ['company_name' => 'Foreign Customer']);
        Customer::create([
            'user_id' => null,
            'company_name' => 'Legacy Customer',
            'company_id' => null,
            'vat_id' => null,
            'first_name' => null,
            'last_name' => null,
            'email' => null,
            'phone_number' => null,
            'street' => null,
            'city' => null,
            'zip' => null,
            'country_code' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('customers.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.id', $ownCustomer->id)
            );
    }

    public function test_customers_index_persists_search_filter_in_props(): void
    {
        $user = User::factory()->create();
        $this->createCustomer($user, [
            'company_name' => 'Orange Trade s.r.o.',
            'company_id' => '87654321',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('customers.index', ['search' => 'Orange']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Index')
                ->where('filters.search', 'Orange')
            );
    }

    public function test_customers_create_page_uses_shared_form_component_in_create_mode(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('customers.create'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Form')
                ->where('mode', 'create')
                ->where('customer', null)
            );
    }

    public function test_customers_edit_page_uses_shared_form_component_in_edit_mode(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer($user, [
            'company_name' => 'Coffee Beans s.r.o.',
            'company_id' => '11223344',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('customers.edit', $customer));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Form')
                ->where('mode', 'edit')
                ->where('customer.id', $customer->id)
                ->where('customer.company_name', $customer->company_name)
            );
    }

    public function test_customers_show_page_renders_with_expected_payload(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer($user, [
            'company_name' => 'Dark Roast Ltd.',
            'company_id' => '44556677',
            'email' => 'billing@darkroast.test',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('customers.show', $customer));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Show')
                ->where('customer.id', $customer->id)
                ->where('customer.company_name', 'Dark Roast Ltd.')
                ->where('customer.company_id', '44556677')
            );
    }

    public function test_customer_is_saved_when_creating_customer(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('customers.store'), [
                'company_name' => 'Vanilla s.r.o.',
                'company_id' => '99887766',
                'vat_id' => 'CZ99887766',
                'first_name' => 'Eva',
                'last_name' => 'Novak',
                'email' => 'eva@vanilla.test',
                'phone_number' => '+420111222333',
                'street' => 'Main 10',
                'city' => 'Prague',
                'zip' => '11000',
                'country_code' => 'cz',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'company_name' => 'Vanilla s.r.o.',
            'company_id' => '99887766',
            'country_code' => 'CZ',
            'user_id' => $user->id,
        ]);
    }

    public function test_customer_is_updated_when_editing_customer(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer($user, [
            'company_name' => 'Apple Trade',
            'company_id' => '10020030',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('customers.update', $customer), [
                'company_name' => 'Apple Trade Premium',
                'company_id' => '10020030',
                'vat_id' => null,
                'first_name' => 'Anna',
                'last_name' => 'Smith',
                'email' => 'anna@apple.test',
                'phone_number' => null,
                'street' => 'Street 5',
                'city' => 'Brno',
                'zip' => '60200',
                'country_code' => 'sk',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'company_name' => 'Apple Trade Premium',
            'country_code' => 'SK',
            'user_id' => $user->id,
        ]);
    }

    public function test_customer_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer($user);

        $response = $this
            ->actingAs($user)
            ->delete(route('customers.destroy', $customer));

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_customer_can_be_saved_with_an_empty_payload(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('customers.store'), []);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'company_name' => null,
            'company_id' => null,
            'street' => null,
            'city' => null,
            'zip' => null,
            'country_code' => null,
            'user_id' => $user->id,
        ]);
    }

    public function test_customers_can_share_the_same_company_id(): void
    {
        $user = User::factory()->create();
        $this->createCustomer($user, ['company_id' => '77777777']);

        $firstResponse = $this
            ->actingAs($user)
            ->post(route('customers.store'), [
                'company_name' => 'First Co',
                'company_id' => '77777777',
            ]);
        $firstResponse->assertSessionHasNoErrors();

        $secondResponse = $this
            ->actingAs($user)
            ->post(route('customers.store'), [
                'company_name' => 'Second Co',
                'company_id' => '77777777',
            ]);

        $secondResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('customers.index'));

        $this->assertSame(3, Customer::where('user_id', $user->id)->where('company_id', '77777777')->count());
    }

    public function test_customer_validation_fails_for_invalid_country_code_and_email(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('customers.store'), [
                'email' => 'not-an-email',
                'country_code' => 'CZE',
            ]);

        $response->assertSessionHasErrors(['country_code', 'email']);
    }

    public function test_customer_fields_can_be_cleared_to_null_on_update(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer($user, [
            'company_name' => 'To Be Cleared',
            'company_id' => 'CLR-001',
            'street' => 'Street 1',
            'city' => 'Prague',
            'zip' => '11000',
            'country_code' => 'CZ',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('customers.update', $customer), [
                'company_name' => '',
                'company_id' => '',
                'vat_id' => '',
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'phone_number' => '',
                'street' => '',
                'city' => '',
                'zip' => '',
                'country_code' => '',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'company_name' => null,
            'company_id' => null,
            'vat_id' => null,
            'first_name' => null,
            'last_name' => null,
            'email' => null,
            'phone_number' => null,
            'street' => null,
            'city' => null,
            'zip' => null,
            'country_code' => null,
        ]);
    }

    public function test_user_gets_404_for_another_users_customer_routes(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $customer = $this->createCustomer($otherUser, ['company_name' => 'Foreign Co']);

        $this->actingAs($user)->get(route('customers.show', $customer))->assertNotFound();
        $this->actingAs($user)->get(route('customers.edit', $customer))->assertNotFound();
        $this->actingAs($user)->put(route('customers.update', $customer), [
            'company_name' => 'Updated',
            'company_id' => null,
            'vat_id' => null,
            'first_name' => null,
            'last_name' => null,
            'email' => null,
            'phone_number' => null,
            'street' => null,
            'city' => null,
            'zip' => null,
            'country_code' => null,
        ])->assertNotFound();
        $this->actingAs($user)->delete(route('customers.destroy', $customer))->assertNotFound();
    }

    private function createCustomer(User $user, array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'user_id' => $user->id,
            'company_name' => 'Sample Customer',
            'company_id' => '11112222',
            'vat_id' => null,
            'first_name' => null,
            'last_name' => null,
            'email' => null,
            'phone_number' => null,
            'street' => 'Sample Street 1',
            'city' => 'Prague',
            'zip' => '11000',
            'country_code' => 'CZ',
        ], $overrides));
    }
}

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
        $customer = $this->createCustomer([
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

    public function test_customers_index_persists_search_filter_in_props(): void
    {
        $user = User::factory()->create();
        $this->createCustomer([
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
        $customer = $this->createCustomer([
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
        $customer = $this->createCustomer([
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
        ]);
    }

    public function test_customer_is_updated_when_editing_customer(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer([
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
        ]);
    }

    public function test_customer_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer();

        $response = $this
            ->actingAs($user)
            ->delete(route('customers.destroy', $customer));

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_customer_validation_fails_for_missing_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('customers.store'), []);

        $response
            ->assertSessionHasErrors([
                'company_name',
                'company_id',
                'street',
                'city',
                'zip',
                'country_code',
            ]);
    }

    public function test_customer_validation_fails_for_duplicate_company_id(): void
    {
        $user = User::factory()->create();
        $this->createCustomer(['company_id' => '77777777']);

        $response = $this
            ->actingAs($user)
            ->post(route('customers.store'), [
                'company_name' => 'Duplicate Co',
                'company_id' => '77777777',
                'street' => 'Line 1',
                'city' => 'Ostrava',
                'zip' => '70030',
                'country_code' => 'CZ',
            ]);

        $response->assertSessionHasErrors(['company_id']);
    }

    public function test_customer_validation_fails_for_invalid_country_code_and_email(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('customers.store'), [
                'company_name' => 'Bad Data',
                'company_id' => '99119911',
                'email' => 'not-an-email',
                'street' => 'Line 1',
                'city' => 'Pilsen',
                'zip' => '30100',
                'country_code' => 'CZE',
            ]);

        $response->assertSessionHasErrors(['country_code', 'email']);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
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

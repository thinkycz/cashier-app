<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BillPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_bills_index_only_lists_authenticated_users_transactions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $customer = $this->createCustomer($user, ['company_name' => 'Own Customer']);

        $ownTransaction = $this->createTransaction($user, [
            'customer_id' => $customer->id,
            'status' => 'cash',
        ]);

        $this->createTransaction($otherUser, ['status' => 'card']);

        Transaction::create([
            'user_id' => null,
            'customer_id' => null,
            'subtotal' => 5,
            'discount' => 0,
            'total' => 5,
            'status' => 'cash',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('bills.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bills/Index')
                ->has('transactions.data', 1)
                ->where('transactions.data.0.id', $ownTransaction->id)
            );
    }

    public function test_bills_show_renders_for_owner(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer($user, ['company_name' => 'Owner Customer']);

        $transaction = $this->createTransaction($user, [
            'customer_id' => $customer->id,
            'status' => 'cash',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('bills.show', $transaction));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bills/Show')
                ->where('bill.id', $transaction->id)
                ->where('bill.customer.id', $customer->id)
            );
    }

    public function test_bills_index_can_filter_by_status(): void
    {
        $user = User::factory()->create();

        $cashTransaction = $this->createTransaction($user, ['status' => 'cash']);
        $this->createTransaction($user, ['status' => 'open']);

        $response = $this
            ->actingAs($user)
            ->get(route('bills.index', ['status' => 'cash']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bills/Index')
                ->where('filters.status', 'cash')
                ->has('transactions.data', 1)
                ->where('transactions.data.0.id', $cashTransaction->id)
                ->where('transactions.data.0.status', 'cash')
            );
    }

    public function test_bills_show_includes_adjustment_fields_when_present(): void
    {
        $user = User::factory()->create();
        $transaction = $this->createTransaction($user, [
            'status' => 'cash',
            'adjustment_type' => 'discount',
            'adjustment_percent' => 10,
            'adjustment_amount' => 2,
            'discount' => 2,
            'subtotal' => 18,
            'total' => 18,
        ]);

        $this->actingAs($user)
            ->get(route('bills.show', $transaction))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bills/Show')
                ->where('bill.adjustment_type', 'discount')
                ->where('bill.adjustment_percent', '10.00')
                ->where('bill.adjustment_amount', '2.00')
            );
    }

    public function test_user_gets_404_for_another_users_bill_show_route(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $transaction = $this->createTransaction($otherUser, ['status' => 'cash']);

        $this->actingAs($user)
            ->get(route('bills.show', $transaction))
            ->assertNotFound();
    }

    private function createTransaction(User $user, array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'user_id' => $user->id,
            'customer_id' => null,
            'subtotal' => 20,
            'discount' => 0,
            'total' => 20,
            'status' => 'open',
        ], $overrides));
    }

    private function createCustomer(User $user, array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'user_id' => $user->id,
            'company_name' => 'Sample Customer',
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
        ], $overrides));
    }
}

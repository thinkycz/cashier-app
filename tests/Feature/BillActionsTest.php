<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_reopen_completed_bill_in_dashboard(): void
    {
        $user = User::factory()->create();
        assert($user instanceof User);
        $customer = $this->createCustomer($user);
        $bill = $this->createTransaction($user, [
            'customer_id' => $customer->id,
            'subtotal' => 200,
            'discount' => 0,
            'adjustment_type' => 'discount',
            'adjustment_percent' => 10,
            'adjustment_amount' => 20,
            'total' => 180,
            'status' => 'cash',
        ]);

        $product = $this->createProduct($user);
        $this->createTransactionItem($bill, $product, [
            'packages' => 1,
            'quantity' => 2,
            'unit_price' => 90,
            'vat_rate' => 21,
            'total' => 180,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('bills.open', $bill));

        $response->assertRedirect(route('dashboard', [
            'active_transaction_id' => $bill->id,
        ]));

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transactions', [
            'id' => $bill->id,
            'status' => 'open',
        ]);
        $this->assertDatabaseCount('transaction_items', 1);
        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $bill->id,
            'product_id' => $product->id,
            'packages' => 1,
            'quantity' => 2,
            'unit_price' => '90.00',
            'vat_rate' => '21.00',
            'total' => '180.00',
        ]);
    }

    public function test_user_can_open_open_bill_in_dashboard_without_creating_duplicate(): void
    {
        $user = User::factory()->create();
        assert($user instanceof User);
        $transaction = $this->createTransaction($user, ['status' => 'open']);

        $response = $this
            ->actingAs($user)
            ->post(route('bills.open', $transaction));

        $response->assertRedirect(route('dashboard', [
            'active_transaction_id' => $transaction->id,
        ]));

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_user_can_delete_bill_and_its_items(): void
    {
        $user = User::factory()->create();
        assert($user instanceof User);
        $bill = $this->createTransaction($user, ['status' => 'cash']);

        $product = $this->createProduct($user);
        $item = $this->createTransactionItem($bill, $product);

        $response = $this
            ->actingAs($user)
            ->delete(route('bills.destroy', $bill));

        $response->assertRedirect(route('bills.index'));

        $this->assertDatabaseMissing('transactions', [
            'id' => $bill->id,
        ]);
        $this->assertDatabaseMissing('transaction_items', [
            'id' => $item->id,
        ]);
    }

    private function createTransaction(User $user, array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'user_id' => $user->id,
            'customer_id' => null,
            'subtotal' => 20,
            'discount' => 0,
            'adjustment_type' => null,
            'adjustment_percent' => 0,
            'adjustment_amount' => 0,
            'total' => 20,
            'status' => 'open',
            'notes' => null,
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

    private function createProduct(User $user, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Sample Product',
            'short_name' => null,
            'ean' => null,
            'vat_rate' => 21,
            'price' => 100,
            'is_active' => true,
        ], $overrides));
    }

    private function createTransactionItem(Transaction $transaction, Product $product, array $overrides = []): TransactionItem
    {
        return TransactionItem::create(array_merge([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'packages' => 1,
            'quantity' => 2,
            'unit_price' => 100,
            'vat_rate' => 21,
            'total' => 200,
        ], $overrides));
    }
}

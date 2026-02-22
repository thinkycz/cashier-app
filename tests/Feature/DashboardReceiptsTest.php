<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardReceiptsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_only_authenticated_users_open_receipts_products_and_customers(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownOpenTransaction = $this->createTransaction($user, ['status' => 'open']);
        $this->createTransaction($user, ['status' => 'cash']);
        $this->createTransaction($otherUser, ['status' => 'open']);
        Transaction::create([
            'user_id' => null,
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'status' => 'open',
        ]);

        $ownProduct = $this->createProduct($user, ['name' => 'Own Product', 'is_active' => true]);
        $this->createProduct($otherUser, ['name' => 'Other Product', 'is_active' => true]);
        $this->createProduct($user, ['name' => 'Inactive Product', 'is_active' => false]);
        Product::create([
            'user_id' => null,
            'name' => 'Legacy Product',
            'short_name' => null,
            'ean' => null,
            'price' => 1,
            'vat_rate' => 21,
            'is_active' => true,
        ]);

        $ownCustomer = $this->createCustomer($user, ['company_name' => 'Own Customer']);
        $this->createCustomer($otherUser, ['company_name' => 'Other Customer']);
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
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('openTransactions', 1)
                ->where('openTransactions.0.id', $ownOpenTransaction->id)
                ->has('products', 1)
                ->where('products.0.id', $ownProduct->id)
                ->has('customers', 1)
                ->where('customers.0.id', $ownCustomer->id)
            );
    }

    public function test_new_receipt_endpoint_creates_open_bill_with_generated_bill_number_and_user_id(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson(route('dashboard.receipts.store'));

        $response
            ->assertCreated()
            ->assertJsonPath('transaction.status', 'open');

        $this->assertDatabaseCount('transactions', 1);
        $transactionId = $response->json('transaction.id');
        $generatedBillNumber = $response->json('transaction.transaction_id');

        $this->assertNotNull($generatedBillNumber);
        $this->assertNotEmpty($generatedBillNumber);
        $this->assertDatabaseHas('transactions', [
            'id' => $transactionId,
            'user_id' => $user->id,
            'status' => 'open',
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
        ]);
    }

    public function test_checkout_recomputes_totals_persists_packages_and_assigns_ad_hoc_products_to_user(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user, [
            'name' => 'Detergent',
            'short_name' => 'DTR',
            'ean' => '1111111111111',
            'price' => 50,
            'vat_rate' => 21,
            'is_active' => true,
        ]);
        $transaction = $this->createTransaction($user);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.checkout', $transaction), [
                'checkout_method' => 'cash',
                'subtotal' => 1,
                'discount' => 5,
                'total' => 1,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'packages' => 2,
                        'quantity' => 3,
                        'unit_price' => 10,
                        'vat_rate' => 21,
                        'total' => 1,
                    ],
                    [
                        'product_id' => null,
                        'product_name' => 'Custom line',
                        'packages' => 4,
                        'quantity' => 2,
                        'unit_price' => 5,
                        'vat_rate' => 0,
                        'total' => 1,
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('transaction.subtotal', '100.00')
            ->assertJsonPath('transaction.total', '95.00')
            ->assertJsonPath('transaction.transaction_items.0.packages', 2)
            ->assertJsonPath('transaction.transaction_items.1.packages', 4);

        $transaction->refresh();

        $this->assertSame('cash', $transaction->status);
        $this->assertSame('100.00', $transaction->subtotal);
        $this->assertSame('95.00', $transaction->total);

        $items = DB::table('transaction_items')
            ->where('transaction_id', $transaction->id)
            ->orderBy('id')
            ->get(['packages', 'quantity', 'unit_price', 'total']);

        $this->assertCount(2, $items);

        $this->assertSame(2, $items[0]->packages);
        $this->assertSame(3, $items[0]->quantity);
        $this->assertSame('10', (string) $items[0]->unit_price);
        $this->assertSame('60', (string) $items[0]->total);

        $this->assertSame(4, $items[1]->packages);
        $this->assertSame(2, $items[1]->quantity);
        $this->assertSame('5', (string) $items[1]->unit_price);
        $this->assertSame('40', (string) $items[1]->total);

        $this->assertDatabaseHas('products', [
            'name' => 'Custom line',
            'user_id' => $user->id,
            'is_active' => 0,
        ]);
    }

    public function test_checkout_rejects_when_discount_exceeds_computed_subtotal(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user, [
            'name' => 'Soap',
            'short_name' => 'SP',
            'ean' => '2222222222222',
            'price' => 10,
            'vat_rate' => 21,
            'is_active' => true,
        ]);
        $transaction = $this->createTransaction($user);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.checkout', $transaction), [
                'checkout_method' => 'card',
                'subtotal' => 0,
                'discount' => 100,
                'total' => 0,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'packages' => 1,
                        'quantity' => 1,
                        'unit_price' => 10,
                        'vat_rate' => 21,
                        'total' => 10,
                    ],
                ],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Discount cannot exceed subtotal.');

        $transaction->refresh();

        $this->assertSame('open', $transaction->status);
        $this->assertSame('0.00', $transaction->subtotal);
        $this->assertSame('0.00', $transaction->total);
        $this->assertDatabaseCount('transaction_items', 0);
    }

    public function test_checkout_rejects_product_ids_from_another_tenant(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignProduct = $this->createProduct($otherUser, ['name' => 'Foreign Product']);
        $transaction = $this->createTransaction($user);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.checkout', $transaction), [
                'checkout_method' => 'cash',
                'items' => [
                    [
                        'product_id' => $foreignProduct->id,
                        'product_name' => $foreignProduct->name,
                        'packages' => 1,
                        'quantity' => 1,
                        'unit_price' => 10,
                        'vat_rate' => 21,
                        'total' => 10,
                    ],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_user_gets_404_when_checking_out_another_users_receipt(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $transaction = $this->createTransaction($otherUser);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.checkout', $transaction), [
                'checkout_method' => 'cash',
                'items' => [
                    [
                        'product_id' => null,
                        'product_name' => 'Own line',
                        'packages' => 1,
                        'quantity' => 1,
                        'unit_price' => 10,
                        'vat_rate' => 21,
                        'total' => 10,
                    ],
                ],
            ]);

        $response->assertNotFound();
    }

    private function createProduct(User $user, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Sample Product',
            'short_name' => null,
            'ean' => null,
            'price' => 100,
            'vat_rate' => 21,
            'is_active' => true,
        ], $overrides));
    }

    private function createTransaction(User $user, array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'user_id' => $user->id,
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
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

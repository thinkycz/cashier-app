<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Dashboard')
                    ->has('openTransactions', 1)
                    ->where('openTransactions.0.id', $ownOpenTransaction->id)
                    ->has('products', 1)
                    ->where('products.0.id', $ownProduct->id)
                    ->where('products.0.name', 'Own Product')
                    ->where('products.0.short_name', null)
                    ->where('products.0.ean', null)
                    ->where('products.0.price', '100.00')
                    ->where('products.0.vat_rate', '21.00')
                    ->has('customers', 1)
                    ->where('customers.0.id', $ownCustomer->id)
            );
    }

    public function test_dashboard_limits_products_payload_to_first_30_active_products_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        for ($index = 1; $index <= 35; $index++) {
            $this->createProduct($user, [
                'name' => sprintf('Product %03d', $index),
                'short_name' => sprintf('P%03d', $index),
                'ean' => sprintf('8591234567%03d', $index),
            ]);
        }

        $this->createProduct($user, [
            'name' => 'Inactive Product',
            'is_active' => false,
        ]);
        $this->createProduct($otherUser, [
            'name' => 'Foreign Product',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Dashboard')
                    ->has('products', 30)
                    ->where('products.0.name', 'Product 001')
                    ->where('products.29.name', 'Product 030')
            );

        $products = $response->viewData('page')['props']['products'];

        $this->assertSame(
            ['id', 'name', 'short_name', 'ean', 'vat_rate', 'price'],
            array_keys($products[0]),
        );
    }

    public function test_dashboard_products_endpoint_returns_paginated_active_products_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        for ($index = 1; $index <= 35; $index++) {
            $this->createProduct($user, [
                'name' => sprintf('Catalog %03d', $index),
                'short_name' => sprintf('C%03d', $index),
            ]);
        }

        $this->createProduct($user, [
            'name' => 'Inactive Hidden',
            'is_active' => false,
        ]);
        $this->createProduct($otherUser, [
            'name' => 'Foreign Hidden',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('dashboard.products.index', [
                'page' => 1,
            ]));

        $response
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 30)
            ->assertJsonPath('meta.total', 35)
            ->assertJsonPath('filters.search', '')
            ->assertJsonCount(30, 'data');
    }

    public function test_dashboard_products_endpoint_searches_name_short_name_and_ean(): void
    {
        $user = User::factory()->create();

        $nameMatch = $this->createProduct($user, [
            'name' => 'Lemon Soda',
            'short_name' => 'LEM',
            'ean' => '8591000000001',
        ]);
        $shortNameMatch = $this->createProduct($user, [
            'name' => 'Sparkling Water',
            'short_name' => 'SPW',
            'ean' => '8591000000002',
        ]);
        $eanMatch = $this->createProduct($user, [
            'name' => 'Orange Juice',
            'short_name' => 'ORJ',
            'ean' => '8591000000999',
        ]);
        $this->createProduct($user, [
            'name' => 'Plain Milk',
            'short_name' => 'MLK',
            'ean' => '8591000000003',
        ]);

        $this
            ->actingAs($user)
            ->getJson(route('dashboard.products.index', ['search' => 'lemon']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $nameMatch->id);

        $this
            ->actingAs($user)
            ->getJson(route('dashboard.products.index', ['search' => 'spw']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $shortNameMatch->id);

        $this
            ->actingAs($user)
            ->getJson(route('dashboard.products.index', ['search' => '00999']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $eanMatch->id);
    }

    public function test_dashboard_products_endpoint_caps_per_page_to_30(): void
    {
        $user = User::factory()->create();

        for ($index = 1; $index <= 35; $index++) {
            $this->createProduct($user, [
                'name' => sprintf('Paginated %03d', $index),
            ]);
        }

        $this
            ->actingAs($user)
            ->getJson(route('dashboard.products.index', [
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertJsonPath('meta.per_page', 30)
            ->assertJsonCount(30, 'data');
    }

    public function test_dashboard_creates_open_receipt_when_user_has_none(): void
    {
        $user = User::factory()->create();
        $this->createTransaction($user, ['status' => 'cash']);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Dashboard')
                    ->has('openTransactions', 1)
            );

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'status' => 'open',
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
        ]);
    }

    public function test_new_receipt_endpoint_creates_open_bill_with_generated_bill_number_and_user_id(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson(route('dashboard.receipts.store'));

        $response
            ->assertCreated()
            ->assertJsonPath('transaction.status', 'open')
            ->assertJsonPath('open_transactions.0.id', $response->json('active_transaction_id'))
            ->assertJsonPath('active_transaction_id', $response->json('transaction.id'));

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

    public function test_open_receipt_customer_can_be_set_to_existing_customer(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer($user, [
            'company_name' => 'Acme',
            'company_id' => '12345678',
        ]);
        $transaction = $this->createTransaction($user, ['customer_id' => null]);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.customer', $transaction), [
                'customer_id' => $customer->id,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('transaction.customer.id', $customer->id)
            ->assertJsonPath('customer.id', $customer->id)
            ->assertJsonPath('created_from_ares', false);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_open_receipt_customer_update_rejects_foreign_customer(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignCustomer = $this->createCustomer($otherUser);
        $transaction = $this->createTransaction($user, ['customer_id' => null]);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.customer', $transaction), [
                'customer_id' => $foreignCustomer->id,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);
    }

    public function test_open_receipt_customer_update_reuses_existing_customer_by_company_id_without_ares_call(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $existingCustomer = $this->createCustomer($user, [
            'company_name' => 'Existing Co',
            'company_id' => '12345678',
        ]);
        $transaction = $this->createTransaction($user, ['customer_id' => null]);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.customer', $transaction), [
                'company_id' => '123 456 78',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('customer.id', $existingCustomer->id)
            ->assertJsonPath('created_from_ares', false);

        $this->assertDatabaseCount('customers', 1);
        Http::assertNothingSent();
    }

    public function test_open_receipt_customer_update_creates_customer_from_ares_when_company_id_unknown(): void
    {
        Http::fake([
            'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/*' => Http::response([
                'ico' => '87654321',
                'dic' => 'CZ87654321',
                'obchodniJmeno' => 'Ares Company s.r.o.',
                'sidlo' => [
                    'textovaAdresa' => 'Street 1, Praha',
                    'nazevObce' => 'Praha',
                    'psc' => '11000',
                    'kodStatu' => 'CZ',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $transaction = $this->createTransaction($user, ['customer_id' => null]);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.customer', $transaction), [
                'company_id' => '87654321',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('created_from_ares', true)
            ->assertJsonPath('customer.company_name', 'Ares Company s.r.o.')
            ->assertJsonPath('customer.company_id', '87654321');

        $this->assertDatabaseHas('customers', [
            'user_id' => $user->id,
            'company_name' => 'Ares Company s.r.o.',
            'company_id' => '87654321',
            'vat_id' => 'CZ87654321',
            'city' => 'Praha',
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_id' => $response->json('customer.id'),
        ]);
    }

    public function test_open_receipt_customer_update_returns_422_when_ares_fails(): void
    {
        Http::fake([
            'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/*' => Http::failedConnection(),
        ]);

        $user = User::factory()->create();
        $transaction = $this->createTransaction($user, ['customer_id' => null]);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.customer', $transaction), [
                'company_id' => '87654321',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Company lookup failed. Please verify the company ID and try again.');

        $this->assertDatabaseMissing('customers', [
            'user_id' => $user->id,
            'company_id' => '87654321',
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_id' => null,
        ]);
    }

    public function test_open_receipt_customer_can_be_cleared(): void
    {
        $user = User::factory()->create();
        $customer = $this->createCustomer($user, [
            'company_id' => '12121212',
        ]);
        $transaction = $this->createTransaction($user, [
            'customer_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.customer', $transaction), [
                'clear_customer' => true,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('transaction.customer', null)
            ->assertJsonPath('customer', null);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_id' => null,
        ]);
    }

    public function test_user_gets_404_when_updating_another_users_receipt_customer(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $transaction = $this->createTransaction($otherUser, ['customer_id' => null]);
        $customer = $this->createCustomer($user, ['company_id' => '11112222']);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.customer', $transaction), [
                'customer_id' => $customer->id,
            ]);

        $response->assertNotFound();
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
                'total' => 1,
                'adjustment_type' => 'discount',
                'adjustment_percent' => 5,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'packages' => 2,
                        'quantity' => 3,
                        'base_unit_price' => 10,
                        'unit_price' => 10,
                        'vat_rate' => 21,
                        'total' => 1,
                    ],
                    [
                        'product_id' => null,
                        'product_name' => 'Custom line',
                        'packages' => 4,
                        'quantity' => 2,
                        'base_unit_price' => 5,
                        'unit_price' => 5,
                        'vat_rate' => 0,
                        'total' => 1,
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('transaction.subtotal', '95.00')
            ->assertJsonPath('transaction.total', '95.00')
            ->assertJsonPath('transaction.discount', '5.00')
            ->assertJsonPath('transaction.adjustment_type', 'discount')
            ->assertJsonPath('transaction.adjustment_percent', '5.00')
            ->assertJsonPath('transaction.adjustment_amount', '5.00')
            ->assertJsonPath('transaction.transaction_items.0.packages', 2)
            ->assertJsonPath('transaction.transaction_items.1.packages', 4)
            ->assertJson(
                fn($json) => $json
                    ->has('open_transactions', 1)
                    ->whereType('active_transaction_id', 'integer')
                    ->etc()
            );

        $transaction->refresh();

        $this->assertSame('cash', $transaction->status);
        $this->assertSame('95.00', $transaction->subtotal);
        $this->assertSame('95.00', $transaction->total);
        $this->assertSame('discount', $transaction->adjustment_type);
        $this->assertSame('5.00', $transaction->adjustment_percent);
        $this->assertSame('5.00', $transaction->adjustment_amount);
        $this->assertSame('5.00', $transaction->discount);

        $items = DB::table('transaction_items')
            ->where('transaction_id', $transaction->id)
            ->orderBy('id')
            ->get(['packages', 'quantity', 'unit_price', 'total']);

        $this->assertCount(2, $items);

        $this->assertSame(2, $items[0]->packages);
        $this->assertSame(3, $items[0]->quantity);
        $this->assertSame('9.5', (string) $items[0]->unit_price);
        $this->assertSame('57', (string) $items[0]->total);

        $this->assertSame(4, $items[1]->packages);
        $this->assertSame(2, $items[1]->quantity);
        $this->assertSame('4.75', (string) $items[1]->unit_price);
        $this->assertSame('38', (string) $items[1]->total);

        $this->assertDatabaseHas('products', [
            'name' => 'Custom line',
            'user_id' => $user->id,
            'price' => 5,
            'is_active' => 1,
        ]);
    }

    public function test_checkout_applies_surcharge_percentage_and_does_not_fill_legacy_discount(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user, ['price' => 10]);
        $transaction = $this->createTransaction($user);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.checkout', $transaction), [
                'checkout_method' => 'card',
                'adjustment_type' => 'surcharge',
                'adjustment_percent' => 10,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'packages' => 1,
                        'quantity' => 2,
                        'base_unit_price' => 10,
                        'unit_price' => 10,
                        'vat_rate' => 21,
                        'total' => 20,
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('transaction.subtotal', '22.00')
            ->assertJsonPath('transaction.total', '22.00')
            ->assertJsonPath('transaction.discount', '0.00')
            ->assertJsonPath('transaction.adjustment_type', 'surcharge')
            ->assertJsonPath('transaction.adjustment_percent', '10.00')
            ->assertJsonPath('transaction.adjustment_amount', '2.00');
    }

    public function test_checkout_ignores_client_unit_price_and_total_and_recomputes_from_base_price(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user, ['price' => 100]);
        $transaction = $this->createTransaction($user);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.checkout', $transaction), [
                'checkout_method' => 'cash',
                'adjustment_type' => 'discount',
                'adjustment_percent' => 10,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'packages' => 1,
                        'quantity' => 1,
                        'base_unit_price' => 100,
                        'unit_price' => 1,
                        'vat_rate' => 21,
                        'total' => 1,
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('transaction.subtotal', '90.00')
            ->assertJsonPath('transaction.total', '90.00')
            ->assertJsonPath('transaction.adjustment_amount', '10.00');

        $item = DB::table('transaction_items')
            ->where('transaction_id', $transaction->id)
            ->first(['unit_price', 'total']);

        $this->assertSame('90', (string) $item->unit_price);
        $this->assertSame('90', (string) $item->total);
    }

    public function test_checkout_uses_product_vat_even_if_client_sends_zero(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user, [
            'name' => 'Milk',
            'price' => 15,
            'vat_rate' => 21,
        ]);
        $transaction = $this->createTransaction($user);

        $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.checkout', $transaction), [
                'checkout_method' => 'cash',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'packages' => 1,
                        'quantity' => 2,
                        'base_unit_price' => 15,
                        'unit_price' => 15,
                        'vat_rate' => 0,
                        'total' => 30,
                    ],
                ],
            ])
            ->assertOk();

        $vatRate = DB::table('transaction_items')
            ->where('transaction_id', $transaction->id)
            ->value('vat_rate');

        $this->assertSame('21', (string) $vatRate);
    }

    public function test_checkout_manual_item_defaults_to_21_when_vat_missing_or_null(): void
    {
        $user = User::factory()->create();
        $transaction = $this->createTransaction($user);

        $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.checkout', $transaction), [
                'checkout_method' => 'card',
                'items' => [
                    [
                        'product_id' => null,
                        'product_name' => 'Manual line',
                        'packages' => 1,
                        'quantity' => 1,
                        'base_unit_price' => 10,
                        'unit_price' => 10,
                        'total' => 10,
                    ],
                ],
            ])
            ->assertOk();

        $vatRate = DB::table('transaction_items')
            ->where('transaction_id', $transaction->id)
            ->value('vat_rate');

        $this->assertSame('21', (string) $vatRate);
    }

    public function test_checkout_manual_item_respects_explicit_manual_vat_if_provided(): void
    {
        $user = User::factory()->create();
        $transaction = $this->createTransaction($user);

        $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.checkout', $transaction), [
                'checkout_method' => 'order',
                'items' => [
                    [
                        'product_id' => null,
                        'product_name' => 'Reduced VAT line',
                        'packages' => 1,
                        'quantity' => 1,
                        'base_unit_price' => 20,
                        'unit_price' => 20,
                        'vat_rate' => 15,
                        'total' => 20,
                    ],
                ],
            ])
            ->assertOk();

        $vatRate = DB::table('transaction_items')
            ->where('transaction_id', $transaction->id)
            ->value('vat_rate');

        $this->assertSame('15', (string) $vatRate);
    }

    public function test_checkout_last_open_receipt_creates_replacement_open_receipt(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user, ['price' => 10]);
        $transaction = $this->createTransaction($user);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.checkout', $transaction), [
                'checkout_method' => 'card',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'packages' => 1,
                        'quantity' => 1,
                        'base_unit_price' => 10,
                        'unit_price' => 10,
                        'vat_rate' => 21,
                        'total' => 10,
                    ],
                ],
            ]);

        $transaction->refresh();

        $response
            ->assertOk()
            ->assertJson(
                fn($json) => $json
                    ->has('open_transactions', 1)
                    ->whereType('active_transaction_id', 'integer')
                    ->etc()
            );

        $this->assertSame('card', $transaction->status);
        $this->assertDatabaseCount('transactions', 2);
        $this->assertSame(1, Transaction::where('user_id', $user->id)->where('status', 'open')->count());
    }

    public function test_checkout_when_other_open_receipts_exist_does_not_create_extra(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user, ['price' => 10]);
        $transactionToCheckout = $this->createTransaction($user);
        $remainingOpenTransaction = $this->createTransaction($user);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('dashboard.receipts.checkout', $transactionToCheckout), [
                'checkout_method' => 'cash',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'packages' => 1,
                        'quantity' => 1,
                        'base_unit_price' => 10,
                        'unit_price' => 10,
                        'vat_rate' => 21,
                        'total' => 10,
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'open_transactions')
            ->assertJsonPath('open_transactions.0.id', $remainingOpenTransaction->id)
            ->assertJsonPath('active_transaction_id', $remainingOpenTransaction->id);

        $this->assertSame(1, Transaction::where('user_id', $user->id)->where('status', 'open')->count());
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_checkout_rejects_when_adjustment_percent_exceeds_100(): void
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
                'adjustment_type' => 'discount',
                'adjustment_percent' => 101,
                'total' => 0,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'packages' => 1,
                        'quantity' => 1,
                        'base_unit_price' => 10,
                        'unit_price' => 10,
                        'vat_rate' => 21,
                        'total' => 10,
                    ],
                ],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['adjustment_percent']);

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
                        'base_unit_price' => 10,
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
                        'base_unit_price' => 10,
                        'unit_price' => 10,
                        'vat_rate' => 21,
                        'total' => 10,
                    ],
                ],
            ]);

        $response->assertNotFound();
    }

    public function test_delete_open_receipt_creates_replacement_when_last_one_deleted(): void
    {
        $user = User::factory()->create();
        $transaction = $this->createTransaction($user);

        $response = $this
            ->actingAs($user)
            ->deleteJson(route('dashboard.receipts.destroy', $transaction));

        $response
            ->assertOk()
            ->assertJson(
                fn($json) => $json
                    ->has('open_transactions', 1)
                    ->whereType('active_transaction_id', 'integer')
            );

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
        $this->assertSame(1, Transaction::where('user_id', $user->id)->where('status', 'open')->count());
    }

    public function test_delete_non_open_receipt_is_rejected(): void
    {
        $user = User::factory()->create();
        $transaction = $this->createTransaction($user, ['status' => 'cash']);

        $response = $this
            ->actingAs($user)
            ->deleteJson(route('dashboard.receipts.destroy', $transaction));

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only open receipts can be deleted.');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'cash',
        ]);
    }

    public function test_user_cannot_delete_another_users_receipt(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $transaction = $this->createTransaction($otherUser);

        $this->actingAs($user)
            ->deleteJson(route('dashboard.receipts.destroy', $transaction))
            ->assertNotFound();
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

<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfflineReceiptSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_transaction_and_items_for_completed_offline_receipt(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user, ['price' => 50, 'vat_rate' => 21]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('offline.receipts.sync'), [
                'receipts' => [
                    [
                        'client_receipt_id' => 'temp:1234',
                        'client_created_at' => now()->toISOString(),
                        'checkout_method' => 'cash',
                        'adjustment_type' => 'discount',
                        'adjustment_percent' => 10,
                        'items' => [
                            [
                                'product_id' => $product->id,
                                'product_name' => $product->name,
                                'packages' => 2,
                                'quantity' => 1,
                                'base_unit_price' => 50,
                                'unit_price' => 50,
                                'vat_rate' => 0,
                                'total' => 100,
                            ],
                        ],
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('results.0.client_receipt_id', 'temp:1234')
            ->assertJsonPath('results.0.status', 'synced')
            ->assertJsonPath('results.0.transaction_id', 1);

        $this->assertDatabaseHas('transactions', [
            'id' => 1,
            'user_id' => $user->id,
            'client_receipt_id' => 'temp:1234',
            'status' => 'cash',
            'subtotal' => 90.00,
            'discount' => 10.00,
            'total' => 90.00,
        ]);

        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => 1,
            'product_id' => $product->id,
            'packages' => 2,
            'quantity' => 1,
            'unit_price' => 45.00,
            'vat_rate' => 21.00,
            'total' => 90.00,
        ]);
    }

    public function test_sync_is_idempotent_for_same_client_receipt_id(): void
    {
        $user = User::factory()->create();

        $payload = [
            'receipts' => [
                [
                    'client_receipt_id' => 'temp:dup-1',
                    'checkout_method' => 'card',
                    'items' => [
                        [
                            'product_id' => null,
                            'product_name' => 'Manual item',
                            'packages' => 1,
                            'quantity' => 1,
                            'base_unit_price' => 10,
                            'unit_price' => 10,
                            'vat_rate' => 21,
                            'total' => 10,
                        ],
                    ],
                ],
            ],
        ];

        $first = $this->actingAs($user)->postJson(route('offline.receipts.sync'), $payload);
        $second = $this->actingAs($user)->postJson(route('offline.receipts.sync'), $payload);

        $first->assertOk()->assertJsonPath('results.0.status', 'synced');
        $second->assertOk()->assertJsonPath('results.0.status', 'synced');
        $this->assertDatabaseCount('transactions', 1);

        $firstTransactionId = $first->json('results.0.transaction_id');
        $secondTransactionId = $second->json('results.0.transaction_id');
        $this->assertSame($firstTransactionId, $secondTransactionId);
    }

    public function test_sync_rejects_invalid_receipt_payload_per_item_without_failing_batch(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson(route('offline.receipts.sync'), [
                'receipts' => [
                    [
                        'client_receipt_id' => 'temp:invalid-1',
                        'checkout_method' => 'cash',
                        'items' => [],
                    ],
                    [
                        'client_receipt_id' => 'temp:valid-1',
                        'checkout_method' => 'order',
                        'items' => [
                            [
                                'product_id' => null,
                                'product_name' => 'Valid line',
                                'packages' => 1,
                                'quantity' => 2,
                                'base_unit_price' => 12,
                                'unit_price' => 12,
                                'vat_rate' => 21,
                                'total' => 24,
                            ],
                        ],
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('results.0.client_receipt_id', 'temp:invalid-1')
            ->assertJsonPath('results.0.status', 'rejected')
            ->assertJsonPath('results.1.client_receipt_id', 'temp:valid-1')
            ->assertJsonPath('results.1.status', 'synced');

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_sync_does_not_link_foreign_customer_and_keeps_note_snapshot(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignCustomerId = DB::table('customers')->insertGetId([
            'user_id' => $otherUser->id,
            'company_name' => 'Other Customer',
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->postJson(route('offline.receipts.sync'), [
                'receipts' => [
                    [
                        'client_receipt_id' => 'temp:customer-snapshot',
                        'checkout_method' => 'cash',
                        'customer_ref' => [
                            'id' => $foreignCustomerId,
                            'name' => 'Jane Offline',
                        ],
                        'items' => [
                            [
                                'product_id' => null,
                                'product_name' => 'Manual line',
                                'packages' => 1,
                                'quantity' => 1,
                                'base_unit_price' => 9,
                                'unit_price' => 9,
                                'vat_rate' => 21,
                                'total' => 9,
                            ],
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'synced');

        $transaction = Transaction::firstOrFail();
        $this->assertNull($transaction->customer_id);
        $this->assertSame('Offline customer: Jane Offline', $transaction->notes);
    }

    public function test_sync_updates_existing_open_transaction_when_source_transaction_id_is_provided(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user, ['price' => 30, 'vat_rate' => 21]);
        $openTransaction = Transaction::create([
            'user_id' => $user->id,
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'status' => 'open',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('offline.receipts.sync'), [
                'receipts' => [
                    [
                        'client_receipt_id' => 'temp:server-open-fallback',
                        'checkout_method' => 'card',
                        'source_transaction_id' => $openTransaction->id,
                        'items' => [
                            [
                                'product_id' => $product->id,
                                'product_name' => $product->name,
                                'packages' => 1,
                                'quantity' => 2,
                                'base_unit_price' => 30,
                                'unit_price' => 30,
                                'vat_rate' => 0,
                                'total' => 60,
                            ],
                        ],
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('results.0.status', 'synced')
            ->assertJsonPath('results.0.transaction_id', $openTransaction->id);

        $openTransaction->refresh();
        $this->assertSame('card', $openTransaction->status);
        $this->assertSame('60.00', $openTransaction->total);
        $this->assertSame('temp:server-open-fallback', $openTransaction->client_receipt_id);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $openTransaction->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'total' => 60.00,
        ]);
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
}

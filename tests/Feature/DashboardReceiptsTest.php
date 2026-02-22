<?php

namespace Tests\Feature;

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

    public function test_dashboard_shows_open_receipts(): void
    {
        $user = User::factory()->create();
        $openTransaction = Transaction::create([
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'status' => 'open',
        ]);
        Transaction::create([
            'subtotal' => 50,
            'discount' => 0,
            'total' => 50,
            'status' => 'cash',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('openTransactions', 1)
                ->where('openTransactions.0.id', $openTransaction->id)
            );
    }

    public function test_new_receipt_endpoint_creates_open_bill_with_generated_bill_number(): void
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
            'status' => 'open',
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
        ]);
    }

    public function test_checkout_recomputes_totals_and_persists_packages(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Detergent',
            'short_name' => 'DTR',
            'ean' => '1111111111111',
            'price' => 50,
            'vat_rate' => 21,
            'is_active' => true,
        ]);
        $transaction = Transaction::create([
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'status' => 'open',
        ]);

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
    }

    public function test_checkout_rejects_when_discount_exceeds_computed_subtotal(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Soap',
            'short_name' => 'SP',
            'ean' => '2222222222222',
            'price' => 10,
            'vat_rate' => 21,
            'is_active' => true,
        ]);
        $transaction = Transaction::create([
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'status' => 'open',
        ]);

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
}

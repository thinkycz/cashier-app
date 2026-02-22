<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'status' => 'completed',
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
}

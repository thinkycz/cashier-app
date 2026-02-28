<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BillPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_bills_index_only_lists_authenticated_users_transactions(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

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
                ->where('filters.status', ['cash', 'card', 'order'])
                ->has('transactions.data', 1)
                ->where('transactions.data.0.id', $ownTransaction->id)
            );
    }

    public function test_bills_show_renders_for_owner(): void
    {
        $user = $this->createUser();
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
        $user = $this->createUser();

        $cashTransaction = $this->createTransaction($user, ['status' => 'cash']);
        $this->createTransaction($user, ['status' => 'open']);

        $response = $this
            ->actingAs($user)
            ->get(route('bills.index', ['status' => 'cash']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bills/Index')
                ->where('filters.status', ['cash'])
                ->has('transactions.data', 1)
                ->where('transactions.data.0.id', $cashTransaction->id)
                ->where('transactions.data.0.status', 'cash')
            );
    }

    public function test_bills_index_defaults_to_non_open_statuses_when_filter_is_missing(): void
    {
        $user = $this->createUser();

        $this->createTransaction($user, ['status' => 'cash']);
        $this->createTransaction($user, ['status' => 'card']);
        $this->createTransaction($user, ['status' => 'order']);
        $this->createTransaction($user, ['status' => 'open']);

        $response = $this
            ->actingAs($user)
            ->get(route('bills.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bills/Index')
                ->where('filters.status', ['cash', 'card', 'order'])
                ->has('transactions.data', 3)
                ->where('transactions.data', function ($transactions) {
                    $statuses = collect($transactions)->pluck('status')->sort()->values()->all();

                    return $statuses === ['card', 'cash', 'order'];
                })
            );
    }

    public function test_bills_index_can_filter_by_multiple_statuses(): void
    {
        $user = $this->createUser();

        $cashTransaction = $this->createTransaction($user, ['status' => 'cash']);
        $cardTransaction = $this->createTransaction($user, ['status' => 'card']);
        $this->createTransaction($user, ['status' => 'open']);

        $response = $this
            ->actingAs($user)
            ->get(route('bills.index', ['status' => ['cash', 'card']]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bills/Index')
                ->where('filters.status', ['cash', 'card'])
                ->has('transactions.data', 2)
                ->where('transactions.data', function ($transactions) use ($cashTransaction, $cardTransaction) {
                    $ids = collect($transactions)->pluck('id')->sort()->values()->all();

                    return $ids === collect([$cashTransaction->id, $cardTransaction->id])->sort()->values()->all();
                })
            );
    }

    public function test_bills_index_returns_no_results_for_explicit_empty_status_selection(): void
    {
        $user = $this->createUser();

        $this->createTransaction($user, ['status' => 'cash']);
        $this->createTransaction($user, ['status' => 'open']);

        $response = $this
            ->actingAs($user)
            ->get(route('bills.index').'?status[]=');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bills/Index')
                ->where('filters.status', [])
                ->has('transactions.data', 0)
            );
    }

    public function test_bills_index_ignores_invalid_status_values(): void
    {
        $user = $this->createUser();

        $cashTransaction = $this->createTransaction($user, ['status' => 'cash']);
        $this->createTransaction($user, ['status' => 'card']);

        $response = $this
            ->actingAs($user)
            ->get(route('bills.index', ['status' => ['cash', 'invalid']]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bills/Index')
                ->where('filters.status', ['cash'])
                ->has('transactions.data', 1)
                ->where('transactions.data.0.id', $cashTransaction->id)
                ->where('transactions.data.0.status', 'cash')
            );
    }

    public function test_bills_show_includes_adjustment_fields_when_present(): void
    {
        $user = $this->createUser();
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
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $transaction = $this->createTransaction($otherUser, ['status' => 'cash']);

        $this->actingAs($user)
            ->get(route('bills.show', $transaction))
            ->assertNotFound();
    }

    public function test_bills_preview_uses_bill_template_for_cash_and_card_statuses(): void
    {
        $user = $this->createUser([
            'company_name' => 'Test Supplier s.r.o.',
            'company_id' => '12345678',
            'vat_id' => 'CZ12345678',
            'street' => 'Main 123',
            'city' => 'Praha',
            'zip' => '11000',
        ]);
        $transaction = $this->createTransaction($user, ['status' => 'cash']);
        $product = $this->createProduct($user);
        $this->createTransactionItem($transaction, $product);

        $response = $this->actingAs($user)->get(route('bills.preview', $transaction));

        $response
            ->assertOk()
            ->assertViewIs('bills.bill')
            ->assertSee('Účtenka č.', false)
            ->assertSee('Test Supplier s.r.o.', false)
            ->assertSee('IČ: 12345678', false)
            ->assertSee('DIČ: CZ12345678', false);
    }

    public function test_bills_preview_uses_quotation_template_for_order_status(): void
    {
        $user = $this->createUser();
        $transaction = $this->createTransaction($user, ['status' => 'order']);
        $product = $this->createProduct($user, ['short_name' => 'Short']);
        $this->createTransactionItem($transaction, $product);

        $response = $this->actingAs($user)->get(route('bills.preview', $transaction));

        $response
            ->assertOk()
            ->assertViewIs('bills.quotation')
            ->assertSee('Objednávka', false);
    }

    public function test_bills_preview_can_render_invoice_document_template(): void
    {
        $user = $this->createUser([
            'company_name' => 'Supplier s.r.o.',
            'company_id' => '12345678',
        ]);
        $customer = $this->createCustomer($user, ['company_name' => 'Buyer a.s.']);
        $transaction = $this->createTransaction($user, [
            'status' => 'cash',
            'customer_id' => $customer->id,
        ]);
        $product = $this->createProduct($user);
        $this->createTransactionItem($transaction, $product);

        $response = $this->actingAs($user)->get(route('bills.preview', $transaction, false).'?document=invoice');

        $response
            ->assertOk()
            ->assertViewIs('documents.invoice')
            ->assertSee('Faktura', false);
    }

    public function test_bills_preview_can_render_non_vat_invoice_document_template(): void
    {
        $user = $this->createUser([
            'company_name' => 'Supplier s.r.o.',
            'company_id' => '12345678',
            'vat_id' => null,
            'bank_account' => '1032861985/5500',
        ]);
        $customer = $this->createCustomer($user, ['company_name' => 'Buyer a.s.', 'company_id' => '87654321']);
        $transaction = $this->createTransaction($user, [
            'status' => 'cash',
            'customer_id' => $customer->id,
            'total' => 70000,
        ]);
        $product = $this->createProduct($user, ['name' => 'Programátorské služby']);
        $this->createTransactionItem($transaction, $product, ['total' => 70000, 'unit_price' => 70000, 'quantity' => 1]);

        $response = $this->actingAs($user)->get(route('bills.preview', $transaction, false).'?document=non_vat_invoice');

        $response
            ->assertOk()
            ->assertViewIs('documents.non_vat_invoice')
            ->assertSee('Neplátce DPH', false)
            ->assertSee('Programátorské služby', false);
    }

    public function test_bills_preview_can_render_delivery_note_document_template(): void
    {
        $user = $this->createUser();
        $transaction = $this->createTransaction($user, ['status' => 'order']);
        $product = $this->createProduct($user);
        $this->createTransactionItem($transaction, $product);

        $response = $this->actingAs($user)->get(route('bills.preview', $transaction, false).'?document=delivery_note');

        $response
            ->assertOk()
            ->assertViewIs('documents.delivery_note')
            ->assertSee('Dodací list', false);
    }

    public function test_user_gets_404_for_another_users_bill_preview_route(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $transaction = $this->createTransaction($otherUser, ['status' => 'cash']);

        $this->actingAs($user)
            ->get(route('bills.preview', $transaction))
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

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
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

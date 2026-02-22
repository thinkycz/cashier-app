<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $this->ensureAtLeastOneOpenReceipt($userId);

        $products = Product::where('user_id', $userId)
            ->where('is_active', true)
            ->get();
        $openTransactions = $this->getOpenReceiptsForUser($userId);
        $customers = Customer::where('user_id', $userId)->get();

        return Inertia::render('Dashboard', [
            'products' => $products,
            'openTransactions' => $openTransactions,
            'customers' => $customers,
        ]);
    }

    public function storeReceipt(): JsonResponse
    {
        $userId = auth()->id();

        $transaction = Transaction::create([
            'user_id' => $userId,
            'customer_id' => null,
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'status' => 'open',
            'notes' => null,
        ])->load(['customer', 'transactionItems.product']);
        $openTransactions = $this->getOpenReceiptsForUser($userId);

        return response()->json([
            'transaction' => $transaction,
            'open_transactions' => $openTransactions,
            'active_transaction_id' => $transaction->id,
        ], 201);
    }

    public function checkoutReceipt(Request $request, Transaction $transaction): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'checkout_method' => ['required', 'in:cash,card,order'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                }),
            ],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.packages' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.total' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($transaction->status !== 'open') {
            return response()->json([
                'message' => 'Only open receipts can be checked out.',
            ], 422);
        }

        $discount = round((float) ($validated['discount'] ?? 0), 2);
        $subtotal = 0.0;
        $normalizedItems = [];

        foreach ($validated['items'] as $item) {
            $packages = (int) $item['packages'];
            $quantity = (int) $item['quantity'];
            $unitPrice = round((float) $item['unit_price'], 2);
            $vatRate = round((float) ($item['vat_rate'] ?? 0), 2);
            $lineTotal = round($packages * $quantity * $unitPrice, 2);

            $normalizedItems[] = [
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'],
                'packages' => $packages,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'vat_rate' => $vatRate,
                'total' => $lineTotal,
            ];

            $subtotal = round($subtotal + $lineTotal, 2);
        }

        $total = round($subtotal - $discount, 2);

        if ($total < 0) {
            return response()->json([
                'message' => 'Discount cannot exceed subtotal.',
            ], 422);
        }

        DB::transaction(function () use ($request, $transaction, $validated, $normalizedItems, $subtotal, $discount, $total) {
            $transaction->transactionItems()->delete();

            foreach ($normalizedItems as $item) {
                $productId = $item['product_id'] ?? null;

                if (!$productId) {
                    $product = Product::create([
                        'user_id' => $request->user()->id,
                        'name' => $item['product_name'],
                        'ean' => null,
                        'vat_rate' => $item['vat_rate'] ?? 0,
                        'price' => $item['unit_price'],
                        'is_active' => false,
                    ]);

                    $productId = $product->id;
                }

                $transaction->transactionItems()->create([
                    'product_id' => $productId,
                    'packages' => $item['packages'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'vat_rate' => $item['vat_rate'] ?? 0,
                    'total' => $item['total'],
                ]);
            }

            $transaction->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'status' => $validated['checkout_method'],
            ]);
        });

        $activeTransaction = $this->ensureAtLeastOneOpenReceipt($userId);
        $openTransactions = $this->getOpenReceiptsForUser($userId);

        return response()->json([
            'transaction' => $transaction->fresh(['customer', 'transactionItems.product']),
            'open_transactions' => $openTransactions,
            'active_transaction_id' => $activeTransaction->id,
        ]);
    }

    public function destroyReceipt(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->status !== 'open') {
            return response()->json([
                'message' => 'Only open receipts can be deleted.',
            ], 422);
        }

        $userId = $request->user()->id;

        $transaction->delete();

        $activeTransaction = $this->ensureAtLeastOneOpenReceipt($userId);
        $openTransactions = $this->getOpenReceiptsForUser($userId);

        return response()->json([
            'open_transactions' => $openTransactions,
            'active_transaction_id' => $activeTransaction->id,
        ]);
    }

    private function ensureAtLeastOneOpenReceipt(int $userId): Transaction
    {
        $latestOpenTransaction = Transaction::where('user_id', $userId)
            ->where('status', 'open')
            ->with(['customer', 'transactionItems.product'])
            ->orderByDesc('created_at')
            ->first();

        if ($latestOpenTransaction) {
            return $latestOpenTransaction;
        }

        return Transaction::create([
            'user_id' => $userId,
            'customer_id' => null,
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'status' => 'open',
            'notes' => null,
        ])->load(['customer', 'transactionItems.product']);
    }

    private function getOpenReceiptsForUser(int $userId): Collection
    {
        return Transaction::where('user_id', $userId)
            ->where('status', 'open')
            ->with(['customer', 'transactionItems.product'])
            ->orderByDesc('created_at')
            ->get();
    }
}

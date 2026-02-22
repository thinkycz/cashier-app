<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $products = Product::where('is_active', true)->get();
        $openTransactions = Transaction::where('status', 'open')
            ->with(['customer', 'transactionItems.product'])
            ->orderByDesc('created_at')
            ->get();
        $customers = Customer::all();

        return Inertia::render('Dashboard', [
            'products' => $products,
            'openTransactions' => $openTransactions,
            'customers' => $customers,
        ]);
    }

    public function storeReceipt(): JsonResponse
    {
        $transaction = Transaction::create([
            'customer_id' => null,
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'status' => 'open',
            'notes' => null,
        ])->load(['customer', 'transactionItems.product']);

        return response()->json([
            'transaction' => $transaction,
        ], 201);
    }

    public function checkoutReceipt(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'checkout_method' => ['required', 'in:cash,card,order'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.total' => ['required', 'numeric', 'min:0'],
        ]);

        if ($transaction->status !== 'open') {
            return response()->json([
                'message' => 'Only open receipts can be checked out.',
            ], 422);
        }

        DB::transaction(function () use ($transaction, $validated) {
            $transaction->transactionItems()->delete();

            foreach ($validated['items'] as $item) {
                $productId = $item['product_id'] ?? null;

                if (!$productId) {
                    $product = Product::create([
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
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'vat_rate' => $item['vat_rate'] ?? 0,
                    'total' => $item['total'],
                ]);
            }

            $transaction->update([
                'subtotal' => $validated['subtotal'],
                'discount' => $validated['discount'] ?? 0,
                'total' => $validated['total'],
                'status' => $validated['checkout_method'],
            ]);
        });

        return response()->json([
            'transaction' => $transaction->fresh(['customer', 'transactionItems.product']),
        ]);
    }
}

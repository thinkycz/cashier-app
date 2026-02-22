<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        ]);

        if ($transaction->status !== 'open') {
            return response()->json([
                'message' => 'Only open receipts can be checked out.',
            ], 422);
        }

        $notes = trim((string) $transaction->notes);
        $checkoutNote = sprintf('checkout_method:%s', $validated['checkout_method']);

        $transaction->update([
            'subtotal' => $validated['subtotal'],
            'discount' => $validated['discount'] ?? 0,
            'total' => $validated['total'],
            'status' => $validated['checkout_method'],
            'notes' => $notes !== '' ? "{$notes}\n{$checkoutNote}" : $checkoutNote,
        ]);

        return response()->json([
            'transaction' => $transaction->fresh(['customer', 'transactionItems.product']),
        ]);
    }
}

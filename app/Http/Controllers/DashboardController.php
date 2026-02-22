<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

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
}

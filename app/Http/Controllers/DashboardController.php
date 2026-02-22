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
            'adjustment_type' => null,
            'adjustment_percent' => 0,
            'adjustment_amount' => 0,
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
        $manualDefaultVatRate = 21.0;

        $validated = $request->validate([
            'checkout_method' => ['required', 'in:cash,card,order'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'adjustment_type' => ['nullable', 'in:discount,surcharge'],
            'adjustment_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
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
            'items.*.base_unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.total' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($transaction->status !== 'open') {
            return response()->json([
                'message' => 'Only open receipts can be checked out.',
            ], 422);
        }

        $adjustmentType = $validated['adjustment_type'] ?? null;
        $adjustmentPercent = $this->roundMoney((float) ($validated['adjustment_percent'] ?? 0));
        $subtotal = 0.0;
        $baseSubtotal = 0.0;
        $normalizedItems = [];
        $requestedProductIds = collect($validated['items'])
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $productVatRates = Product::where('user_id', $userId)
            ->whereIn('id', $requestedProductIds)
            ->pluck('vat_rate', 'id');

        foreach ($validated['items'] as $item) {
            $productId = isset($item['product_id']) ? (int) $item['product_id'] : null;
            $packages = (int) $item['packages'];
            $quantity = (int) $item['quantity'];
            $baseUnitPrice = $this->roundMoney((float) $item['base_unit_price']);
            $unitPrice = $this->applyAdjustmentToUnitPrice($baseUnitPrice, $adjustmentType, $adjustmentPercent);
            $hasVatRate = array_key_exists('vat_rate', $item) && $item['vat_rate'] !== null;
            $vatRate = $productId
                ? $this->roundMoney((float) ($productVatRates[$productId] ?? 0))
                : $this->roundMoney((float) ($hasVatRate ? $item['vat_rate'] : $manualDefaultVatRate));
            $baseLineTotal = $this->roundMoney($packages * $quantity * $baseUnitPrice);
            $lineTotal = $this->roundMoney($packages * $quantity * $unitPrice);

            $normalizedItems[] = [
                'product_id' => $productId,
                'product_name' => $item['product_name'],
                'packages' => $packages,
                'quantity' => $quantity,
                'base_unit_price' => $baseUnitPrice,
                'unit_price' => $unitPrice,
                'vat_rate' => $vatRate,
                'total' => $lineTotal,
            ];

            $baseSubtotal = $this->roundMoney($baseSubtotal + $baseLineTotal);
            $subtotal = $this->roundMoney($subtotal + $lineTotal);
        }

        $adjustmentAmount = $adjustmentType
            ? $this->roundMoney($baseSubtotal * ($adjustmentPercent / 100))
            : 0.0;
        $legacyDiscount = $adjustmentType === 'discount' ? $adjustmentAmount : 0.0;
        $total = $subtotal;

        DB::transaction(function () use (
            $request,
            $transaction,
            $validated,
            $normalizedItems,
            $subtotal,
            $legacyDiscount,
            $adjustmentType,
            $adjustmentPercent,
            $adjustmentAmount,
            $total
        ) {
            $transaction->transactionItems()->delete();

            foreach ($normalizedItems as $item) {
                $productId = $item['product_id'] ?? null;

                if (!$productId) {
                    $product = Product::create([
                        'user_id' => $request->user()->id,
                        'name' => $item['product_name'],
                        'ean' => null,
                        'vat_rate' => $item['vat_rate'] ?? 0,
                        'price' => $item['base_unit_price'],
                        'is_active' => true,
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
                'discount' => $legacyDiscount,
                'adjustment_type' => $adjustmentType,
                'adjustment_percent' => $adjustmentPercent,
                'adjustment_amount' => $adjustmentAmount,
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
            'adjustment_type' => null,
            'adjustment_percent' => 0,
            'adjustment_amount' => 0,
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

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }

    private function applyAdjustmentToUnitPrice(float $baseUnitPrice, ?string $adjustmentType, float $adjustmentPercent): float
    {
        if (!$adjustmentType || $adjustmentPercent <= 0) {
            return $baseUnitPrice;
        }

        if ($adjustmentType === 'discount') {
            return $this->roundMoney($baseUnitPrice * (1 - ($adjustmentPercent / 100)));
        }

        if ($adjustmentType === 'surcharge') {
            return $this->roundMoney($baseUnitPrice * (1 + ($adjustmentPercent / 100)));
        }

        return $baseUnitPrice;
    }
}

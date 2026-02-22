<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class OfflineReceiptSyncController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receipts' => ['required', 'array', 'min:1'],
            'receipts.*' => ['required', 'array'],
        ]);

        $userId = (int) $request->user()->id;
        $results = [];

        foreach ($validated['receipts'] as $index => $payload) {
            $receiptValidator = Validator::make($payload, [
                'client_receipt_id' => ['required', 'string', 'max:100'],
                'client_created_at' => ['nullable', 'date'],
                'checkout_method' => ['required', 'in:cash,card,order'],
                'source_transaction_id' => ['nullable', 'integer', 'min:1'],
                'subtotal' => ['nullable', 'numeric', 'min:0'],
                'total' => ['nullable', 'numeric', 'min:0'],
                'adjustment_type' => ['nullable', 'in:discount,surcharge'],
                'adjustment_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'customer_ref' => ['nullable', 'array'],
                'customer_ref.id' => ['nullable', 'integer', 'min:1'],
                'customer_ref.name' => ['nullable', 'string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:2000'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.product_id' => ['nullable', 'integer', 'min:1'],
                'items.*.product_name' => ['required', 'string', 'max:255'],
                'items.*.packages' => ['required', 'integer', 'min:1'],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
                'items.*.base_unit_price' => ['required', 'numeric', 'min:0'],
                'items.*.unit_price' => ['required', 'numeric', 'min:0'],
                'items.*.vat_rate' => ['nullable', 'numeric', 'min:0'],
                'items.*.total' => ['nullable', 'numeric', 'min:0'],
            ]);

            if ($receiptValidator->fails()) {
                $results[] = [
                    'client_receipt_id' => (string) ($payload['client_receipt_id'] ?? "invalid:{$index}"),
                    'status' => 'rejected',
                    'error_code' => 'validation_failed',
                    'message' => $receiptValidator->errors()->first(),
                ];
                continue;
            }

            $normalized = $receiptValidator->validated();
            $clientReceiptId = $normalized['client_receipt_id'];

            try {
                $existing = Transaction::where('user_id', $userId)
                    ->where('client_receipt_id', $clientReceiptId)
                    ->first();

                if ($existing) {
                    $results[] = $this->syncedResult($existing, $clientReceiptId);
                    continue;
                }

                $transaction = DB::transaction(fn () => $this->createSyncedTransaction($userId, $normalized));
                $results[] = $this->syncedResult($transaction, $clientReceiptId);
            } catch (Throwable $exception) {
                report($exception);

                $results[] = [
                    'client_receipt_id' => $clientReceiptId,
                    'status' => 'rejected',
                    'error_code' => 'sync_failed',
                    'message' => 'Receipt sync failed. Please retry.',
                ];
            }
        }

        return response()->json([
            'results' => $results,
        ]);
    }

    private function createSyncedTransaction(int $userId, array $payload): Transaction
    {
        $manualDefaultVatRate = 21.0;
        $adjustmentType = $payload['adjustment_type'] ?? null;
        $adjustmentPercent = $this->roundMoney((float) ($payload['adjustment_percent'] ?? 0));

        $requestedProductIds = collect($payload['items'])
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $availableProducts = Product::query()
            ->where('user_id', $userId)
            ->whereIn('id', $requestedProductIds)
            ->get()
            ->keyBy('id');

        $normalizedItems = [];
        $baseSubtotal = 0.0;
        $subtotal = 0.0;

        foreach ($payload['items'] as $item) {
            $requestedProductId = isset($item['product_id']) ? (int) $item['product_id'] : null;
            $packages = (int) $item['packages'];
            $quantity = (int) $item['quantity'];
            $baseUnitPrice = $this->roundMoney((float) $item['base_unit_price']);
            $unitPrice = $this->applyAdjustmentToUnitPrice($baseUnitPrice, $adjustmentType, $adjustmentPercent);
            $hasVatRate = array_key_exists('vat_rate', $item) && $item['vat_rate'] !== null;
            $product = $requestedProductId ? $availableProducts->get($requestedProductId) : null;
            $productId = $product?->id;
            $vatRate = $product
                ? $this->roundMoney((float) $product->vat_rate)
                : $this->roundMoney((float) ($hasVatRate ? $item['vat_rate'] : $manualDefaultVatRate));
            $lineTotal = $this->roundMoney($packages * $quantity * $unitPrice);
            $baseLineTotal = $this->roundMoney($packages * $quantity * $baseUnitPrice);

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
        $discount = $adjustmentType === 'discount' ? $adjustmentAmount : 0.0;
        $customerId = $this->resolveCustomerId($userId, $payload['customer_ref'] ?? null);
        $notes = $this->buildNotes($payload, $customerId);

        $transaction = null;
        $sourceTransactionId = isset($payload['source_transaction_id']) ? (int) $payload['source_transaction_id'] : null;

        if ($sourceTransactionId) {
            $sourceTransaction = Transaction::where('user_id', $userId)
                ->where('id', $sourceTransactionId)
                ->where('status', 'open')
                ->first();

            if ($sourceTransaction) {
                $sourceTransaction->transactionItems()->delete();
                $sourceTransaction->update([
                    'client_receipt_id' => $payload['client_receipt_id'],
                    'customer_id' => $customerId,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'adjustment_type' => $adjustmentType,
                    'adjustment_percent' => $adjustmentPercent,
                    'adjustment_amount' => $adjustmentAmount,
                    'total' => $subtotal,
                    'status' => $payload['checkout_method'],
                    'notes' => $notes,
                ]);
                $transaction = $sourceTransaction->fresh();
            }
        }

        if (!$transaction) {
            $transaction = Transaction::create([
                'user_id' => $userId,
                'client_receipt_id' => $payload['client_receipt_id'],
                'customer_id' => $customerId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'adjustment_type' => $adjustmentType,
                'adjustment_percent' => $adjustmentPercent,
                'adjustment_amount' => $adjustmentAmount,
                'total' => $subtotal,
                'status' => $payload['checkout_method'],
                'notes' => $notes,
            ]);
        }

        foreach ($normalizedItems as $item) {
            $productId = $item['product_id'];

            if (!$productId) {
                $createdProduct = Product::create([
                    'user_id' => $userId,
                    'name' => $item['product_name'],
                    'ean' => null,
                    'vat_rate' => $item['vat_rate'],
                    'price' => $item['base_unit_price'],
                    'is_active' => true,
                ]);
                $productId = $createdProduct->id;
            }

            $transaction->transactionItems()->create([
                'product_id' => $productId,
                'packages' => $item['packages'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'vat_rate' => $item['vat_rate'],
                'total' => $item['total'],
            ]);
        }

        return $transaction;
    }

    private function resolveCustomerId(int $userId, ?array $customerRef): ?int
    {
        $customerId = isset($customerRef['id']) ? (int) $customerRef['id'] : null;
        if (!$customerId) {
            return null;
        }

        return Customer::where('user_id', $userId)
            ->where('id', $customerId)
            ->value('id');
    }

    private function buildNotes(array $payload, ?int $customerId): ?string
    {
        $notes = trim((string) ($payload['notes'] ?? ''));

        if ($customerId || empty($payload['customer_ref']['name'])) {
            return $notes !== '' ? $notes : null;
        }

        $offlineCustomerNote = 'Offline customer: '.$payload['customer_ref']['name'];

        if ($notes === '') {
            return $offlineCustomerNote;
        }

        return $notes."\n".$offlineCustomerNote;
    }

    private function syncedResult(Transaction $transaction, string $clientReceiptId): array
    {
        return [
            'client_receipt_id' => $clientReceiptId,
            'status' => 'synced',
            'transaction_id' => $transaction->id,
            'transaction_code' => $transaction->transaction_id,
        ];
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

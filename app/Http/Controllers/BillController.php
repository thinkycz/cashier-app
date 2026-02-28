<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Transaction;

class BillController extends Controller
{
    private const ALLOWED_STATUSES = ['open', 'cash', 'card', 'order'];
    private const DEFAULT_STATUSES = ['cash', 'card', 'order'];
    private const ALLOWED_DOCUMENTS = ['bill', 'invoice', 'delivery_note'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $statuses = $this->normalizeStatuses($request);

        $query = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->with('customer');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($subQuery) use ($search) {
                      $subQuery->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('company_name', 'like', "%{$search}%")
                          ->orWhere('company_id', 'like', "%{$search}%");
                  });
            });
        }

        if (count($statuses) > 0) {
            $query->whereIn('status', $statuses);
        } else {
            $query->whereRaw('1 = 0');
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Bills/Index', [
            'transactions' => $transactions,
            'filters' => [
                'search' => $request->get('search', ''),
                'status' => $statuses,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $bill)
    {
        $bill->load(['customer', 'transactionItems.product']);

        return Inertia::render('Bills/Show', [
            'bill' => $bill,
        ]);
    }

    public function open(Transaction $bill)
    {
        if ($bill->status !== 'open') {
            $bill->update([
                'status' => 'open',
            ]);
        }

        return redirect()->route('dashboard', [
            'active_transaction_id' => $bill->id,
        ]);
    }

    public function destroy(Transaction $bill)
    {
        $bill->delete();

        return redirect()
            ->route('bills.index')
            ->with('success', 'Bill deleted.');
    }

    /**
     * Display print preview for the specified bill.
     */
    public function preview(Transaction $bill, Request $request)
    {
        $bill->load(['customer', 'transactionItems.product']);
        $requestedDocumentType = $request->string('document')->toString();
        $documentType = in_array($requestedDocumentType, self::ALLOWED_DOCUMENTS, true)
            ? $requestedDocumentType
            : 'bill';

        $supplier = $request->user();
        $supplierFullName = trim(implode(' ', array_filter([
            $supplier?->first_name,
            $supplier?->last_name,
        ])));
        $customerFullName = trim(implode(' ', array_filter([
            $bill->customer?->first_name,
            $bill->customer?->last_name,
        ])));

        $billItems = $bill->transactionItems
            ->sortBy('id')
            ->values()
            ->map(function ($item, $index) {
                return (object) [
                    'id' => $item->id,
                    'order_column' => $index + 1,
                    'name' => $item->product?->name ?? 'Položka',
                    'product' => $item->product,
                    'packs' => (int) ($item->packages ?: 1),
                    'quantity' => (int) $item->quantity,
                    'vat_rate' => number_format((float) $item->vat_rate, 2, ',', ' ') . ' %',
                    'calculated_unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total,
                    'vat_rate_value' => (float) $item->vat_rate,
                ];
            });

        $vatSummary = $this->buildVatSummary($billItems);

        $billViewData = (object) [
            'id' => $bill->id,
            'number' => $bill->transaction_id,
            'created_at' => $bill->created_at,
            'total_price' => (float) $bill->total,
            'vat_rates' => array_keys($vatSummary),
            'supplier' => (object) [
                'company_name' => $supplier?->company_name,
                'full_name' => $supplierFullName !== '' ? $supplierFullName : null,
                'street' => $supplier?->street,
                'zip' => $supplier?->zip,
                'city' => $supplier?->city,
                'company_id' => $supplier?->company_id,
                'vat_id' => $supplier?->vat_id,
                'bank_account' => $supplier?->bank_account,
            ],
            'customer' => (object) [
                'company_name' => $bill->customer?->company_name,
                'full_name' => $customerFullName !== '' ? $customerFullName : null,
                'street' => $bill->customer?->street,
                'zip' => $bill->customer?->zip,
                'city' => $bill->customer?->city,
                'company_id' => $bill->customer?->company_id,
                'vat_id' => $bill->customer?->vat_id,
                'email' => $bill->customer?->email,
                'phone_number' => $bill->customer?->phone_number,
            ],
        ];

        $view = match ($documentType) {
            'invoice' => 'documents.invoice',
            'delivery_note' => 'documents.delivery_note',
            default => $bill->status === 'order' ? 'bills.quotation' : 'bills.bill',
        };

        return view($view, [
            'bill' => $billViewData,
            'billItems' => $billItems,
            'vatSummary' => $vatSummary,
            'embedded' => $request->boolean('embedded'),
        ]);
    }

    private function buildVatSummary(Collection $billItems): array
    {
        $summary = [];

        foreach ($billItems as $item) {
            $rate = (float) $item->vat_rate_value;
            $label = number_format($rate, 2, ',', ' ') . ' %';
            $totalInclVat = (float) $item->total_price;
            $divider = 1 + ($rate / 100);
            $totalExclVat = $divider > 0 ? ($totalInclVat / $divider) : $totalInclVat;
            $vatAmount = $totalInclVat - $totalExclVat;

            if (!isset($summary[$label])) {
                $summary[$label] = [
                    'base' => 0.0,
                    'vat' => 0.0,
                    'total' => 0.0,
                ];
            }

            $summary[$label]['base'] += $totalExclVat;
            $summary[$label]['vat'] += $vatAmount;
            $summary[$label]['total'] += $totalInclVat;
        }

        return $summary;
    }

    private function normalizeStatuses(Request $request): array
    {
        $statusFilterExists = $request->query->has('status');
        $rawStatuses = $statusFilterExists ? $request->query('status') : self::DEFAULT_STATUSES;

        if (is_string($rawStatuses)) {
            $rawStatuses = [$rawStatuses];
        }

        if (!is_array($rawStatuses)) {
            return [];
        }

        return collect($rawStatuses)
            ->filter(fn ($status) => is_string($status))
            ->map(fn (string $status) => trim($status))
            ->filter(fn (string $status) => $status !== '')
            ->filter(fn (string $status) => in_array($status, self::ALLOWED_STATUSES, true))
            ->unique()
            ->values()
            ->all();
    }
}

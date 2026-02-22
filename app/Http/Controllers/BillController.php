<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Transaction;

class BillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
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

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Bills/Index', [
            'transactions' => $transactions,
            'filters' => $request->only(['search', 'status']),
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

    /**
     * Display print preview for the specified bill.
     */
    public function preview(Transaction $bill, Request $request)
    {
        $bill->load(['transactionItems.product']);

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
            'number' => $bill->transaction_id,
            'created_at' => $bill->created_at,
            'total_price' => (float) $bill->total,
            'vat_rates' => array_keys($vatSummary),
        ];

        $view = $bill->status === 'order' ? 'bills.quotation' : 'bills.bill';

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
}

@extends('documents.base')

@section('title', $bill->number)

@section('document')
    @php
        $formatCurrency = static fn (float $value): string => number_format($value, 2, ',', ' ') . ' Kč';
        $hasCustomer = (bool) ($bill->customer->company_name
            || $bill->customer->full_name
            || $bill->customer->street
            || $bill->customer->zip
            || $bill->customer->city
            || $bill->customer->company_id
            || $bill->customer->vat_id);

        $issuedAt = $bill->created_at?->format('d. m. Y');
        $taxableAt = $bill->created_at?->format('d. m. Y');
        $dueAt = $bill->created_at?->copy()?->addDays(7)?->format('d. m. Y');
        $variableSymbol = isset($bill->id) ? str_pad((string) $bill->id, 10, '0', STR_PAD_LEFT) : '-';
    @endphp

    <div class="flex min-h-[295mm] flex-col px-[18mm] pb-[14mm] pt-[12mm] text-[11px] leading-snug text-slate-900">
        <div class="pt-6">
            <div class="border-t-2 border-slate-400"></div>
            <div class="mt-3 flex items-baseline justify-between gap-6">
                <p class="text-2xl font-semibold tracking-tight">Faktura - daňový doklad</p>
                <p class="text-2xl font-semibold tracking-tight">{{ $bill->number }}</p>
            </div>
        </div>

        <div class="mt-12 grid grid-cols-2 gap-x-16">
            <div>
                <p class="mb-4 text-[10px] uppercase tracking-wide text-slate-500">Dodavatel</p>
                @if($bill->supplier->company_name)
                    <p class="text-sm font-semibold">{{ $bill->supplier->company_name }}</p>
                @endif
                @if($bill->supplier->full_name)
                    <p class="font-semibold">{{ $bill->supplier->full_name }}</p>
                @endif
                @if($bill->supplier->street)
                    <p>{{ $bill->supplier->street }}</p>
                @endif
                @if($bill->supplier->zip || $bill->supplier->city)
                    <p>{{ trim(($bill->supplier->zip ?? '') . ' ' . ($bill->supplier->city ?? '')) }}</p>
                @endif

                <div class="mt-8 space-y-1">
                    <div class="flex items-baseline justify-between gap-6">
                        <span class="text-slate-500">IČ</span>
                        <span class="font-medium">{{ $bill->supplier->company_id ?: '-' }}</span>
                    </div>
                    <div class="flex items-baseline justify-between gap-6">
                        <span class="text-slate-500">DIČ</span>
                        <span class="font-medium">{{ $bill->supplier->vat_id ?: '-' }}</span>
                    </div>
                </div>
            </div>

            <div>
                <p class="mb-4 text-[10px] uppercase tracking-wide text-slate-500">Odběratel</p>
                @if($hasCustomer)
                    @if($bill->customer->company_name)
                        <p class="text-sm font-semibold">{{ $bill->customer->company_name }}</p>
                    @endif
                    @if($bill->customer->full_name)
                        <p class="font-semibold">{{ $bill->customer->full_name }}</p>
                    @endif
                    @if($bill->customer->street)
                        <p>{{ $bill->customer->street }}</p>
                    @endif
                    @if($bill->customer->zip || $bill->customer->city)
                        <p>{{ trim(($bill->customer->zip ?? '') . ' ' . ($bill->customer->city ?? '')) }}</p>
                    @endif
                @else
                    <p class="text-sm font-semibold">Bez odběratele</p>
                @endif

                <div class="mt-8 space-y-1">
                    <div class="flex items-baseline justify-between gap-6">
                        <span class="text-slate-500">IČ</span>
                        <span class="font-medium">{{ $bill->customer->company_id ?: '-' }}</span>
                    </div>
                    <div class="flex items-baseline justify-between gap-6">
                        <span class="text-slate-500">DIČ</span>
                        <span class="font-medium">{{ $bill->customer->vat_id ?: '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-10 grid grid-cols-2 gap-x-16">
            <div class="space-y-1">
                <div class="flex items-baseline justify-between gap-6">
                    <span class="text-slate-500">Datum vystavení</span>
                    <span class="font-medium">{{ $issuedAt ?: '-' }}</span>
                </div>
                <div class="flex items-baseline justify-between gap-6">
                    <span class="text-slate-500">Datum zdanitelného plnění</span>
                    <span class="font-medium">{{ $taxableAt ?: '-' }}</span>
                </div>
                <div class="flex items-baseline justify-between gap-6">
                    <span class="text-slate-500">Datum splatnosti</span>
                    <span class="font-medium">{{ $dueAt ?: '-' }}</span>
                </div>
            </div>

            <div class="space-y-1">
                <div class="flex items-baseline justify-between gap-6">
                    <span class="text-slate-500">Číslo účtu</span>
                    <span class="font-medium">{{ $bill->supplier->bank_account ?: '-' }}</span>
                </div>
                <div class="flex items-baseline justify-between gap-6">
                    <span class="text-slate-500">Variabilní symbol</span>
                    <span class="font-medium">{{ $variableSymbol }}</span>
                </div>
            </div>
        </div>

        <div class="mt-20 flex-1">
            <table class="w-full border-collapse">
                <thead>
                <tr class="border-b border-slate-300">
                    <th class="py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-500">#</th>
                    <th class="py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-500">Product</th>
                    <th class="py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-500">EAN</th>
                    <th class="py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-slate-500">Packages</th>
                    <th class="py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-slate-500">Qty</th>
                    <th class="py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-slate-500">Unit Price</th>
                    <th class="py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-slate-500">VAT</th>
                    <th class="py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-slate-500">Total Price</th>
                </tr>
                </thead>
                <tbody>
                @foreach($billItems as $item)
                    @php
                        $vatRateValue = (float) $item->vat_rate_value;
                        $unitPriceInclVat = (float) $item->calculated_unit_price;
                        $divider = 1 + ($vatRateValue / 100);
                        $unitPriceExclVat = $divider > 0 ? ($unitPriceInclVat / $divider) : $unitPriceInclVat;
                        $unitPriceVatAmount = $unitPriceInclVat - $unitPriceExclVat;
                    @endphp
                    <tr class="border-b border-slate-200">
                        <td class="py-3 pr-3 align-top">{{ $item->order_column }}</td>
                        <td class="py-3 pr-4 align-top font-medium">{{ $item->name }}</td>
                        <td class="py-3 pr-4 align-top font-mono text-[10px] text-slate-700">{{ $item->product?->ean ?: '-' }}</td>
                        <td class="py-3 pl-4 text-right align-top">{{ $item->packs }}</td>
                        <td class="py-3 pl-4 text-right align-top">{{ $item->quantity }}</td>
                        <td class="py-3 pl-4 text-right align-top">
                            <p>{{ $formatCurrency($unitPriceExclVat) }}</p>
                            <p class="mt-0.5 text-[10px] leading-tight text-slate-500">{{ $formatCurrency($unitPriceInclVat) }}</p>
                        </td>
                        <td class="py-3 pl-4 text-right align-top">
                            <p>{{ $formatCurrency($unitPriceVatAmount) }}</p>
                            <p class="mt-0.5 text-[10px] leading-tight text-slate-500">{{ $item->vat_rate }}</p>
                        </td>
                        <td class="py-3 pl-4 text-right align-top font-medium">{{ $formatCurrency((float) $item->total_price) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-auto pt-6">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">DPH rekapitulace</p>
            <div class="mt-2 border-t border-slate-300"></div>
            <table class="mt-2 w-full border-collapse">
                <thead>
                <tr class="border-b border-slate-300">
                    <th class="py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-500">Sazba</th>
                    <th class="py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-slate-500">Základ DPH</th>
                    <th class="py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-slate-500">DPH</th>
                    <th class="py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-slate-500">Celkem</th>
                </tr>
                </thead>
                <tbody>
                @foreach($bill->vat_rates as $vat)
                    <tr class="border-b border-slate-200">
                        <td class="py-2 pr-4 font-medium">Sazba {{ $vat }}</td>
                        <td class="py-2 text-right text-slate-700">{{ $formatCurrency($vatSummary[$vat]['base'] ?? 0) }}</td>
                        <td class="py-2 pl-4 text-right text-slate-700">{{ $formatCurrency($vatSummary[$vat]['vat'] ?? 0) }}</td>
                        <td class="py-2 pl-4 text-right font-medium">{{ $formatCurrency($vatSummary[$vat]['total'] ?? 0) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="mt-8 flex justify-end">
                <div class="w-[280px] text-right">
                    <div class="border-t border-slate-500"></div>
                    <p class="mt-2 text-2xl font-semibold">{{ $formatCurrency($bill->total_price) }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

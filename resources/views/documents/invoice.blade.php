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
    @endphp

    <div class="text-xs">
        <div class="flex justify-between pb-4 border-b-2 border-black mb-6">
            <div class="flex flex-col">
                <p class="text-lg font-semibold">Faktura</p>
                <p class="font-semibold">{{ $bill->number }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 grid-rows-2 gap-6 mb-6">
            <div>
                <p class="uppercase text-gray-600 mb-4">Dodavatel</p>
                @if($bill->supplier->company_name)
                    <p class="text-sm font-medium">{{ $bill->supplier->company_name }}</p>
                @endif
                @if($bill->supplier->full_name)
                    <p>{{ $bill->supplier->full_name }}</p>
                @endif
                @if($bill->supplier->street)
                    <p>{{ $bill->supplier->street }}</p>
                @endif
                @if($bill->supplier->zip || $bill->supplier->city)
                    <p>{{ trim(($bill->supplier->zip ?? '') . ' ' . ($bill->supplier->city ?? '')) }}</p>
                @endif
                @if($bill->supplier->company_id)
                    <p>IČ: {{ $bill->supplier->company_id }}</p>
                @endif
                @if($bill->supplier->vat_id)
                    <p>DIČ: {{ $bill->supplier->vat_id }}</p>
                @endif
            </div>

            <div>
                <p class="uppercase text-gray-600 mb-4">Odběratel</p>
                @if($hasCustomer)
                    @if($bill->customer->company_name)
                        <p class="text-sm font-medium">{{ $bill->customer->company_name }}</p>
                    @endif
                    @if($bill->customer->full_name)
                        <p>{{ $bill->customer->full_name }}</p>
                    @endif
                    @if($bill->customer->street)
                        <p>{{ $bill->customer->street }}</p>
                    @endif
                    @if($bill->customer->zip || $bill->customer->city)
                        <p>{{ trim(($bill->customer->zip ?? '') . ' ' . ($bill->customer->city ?? '')) }}</p>
                    @endif
                    @if($bill->customer->company_id)
                        <p>IČ: {{ $bill->customer->company_id }}</p>
                    @endif
                    @if($bill->customer->vat_id)
                        <p>DIČ: {{ $bill->customer->vat_id }}</p>
                    @endif
                @else
                    <p class="text-sm font-medium">Bez odběratele</p>
                @endif
            </div>

            <div>
                <p class="uppercase text-gray-600 mb-4">Údaje dokladu</p>
                <p>Datum vystavení: {{ $bill->created_at?->format('d.m.Y') }}</p>
            </div>
        </div>

        <table class="w-full">
            <thead class="border-b border-black">
            <tr>
                <td class="py-2 text-left font-semibold">#</td>
                <td class="text-left font-semibold">Název</td>
                <td class="text-right font-semibold">Počet</td>
                <td class="text-right font-semibold">Cena za MJ</td>
                <td class="text-right font-semibold">DPH</td>
                <td class="text-right font-semibold">Cena za MJ vč. DPH</td>
                <td class="text-right font-semibold">Sazba DPH</td>
                <td class="text-right font-semibold">Celkem s DPH</td>
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
                <tr>
                    <td class="py-2 text-left">{{ $item->order_column }}</td>
                    <td class="text-left">{{ $item->name }}</td>
                    <td class="text-right">{{ $item->packs }} x {{ $item->quantity }}</td>
                    <td class="text-right">{{ $formatCurrency($unitPriceExclVat) }}</td>
                    <td class="text-right">{{ $formatCurrency($unitPriceVatAmount) }}</td>
                    <td class="text-right">{{ $formatCurrency($unitPriceInclVat) }}</td>
                    <td class="text-right">{{ $item->vat_rate }}</td>
                    <td class="text-right">{{ $formatCurrency($item->total_price) }}</td>
                </tr>
            @endforeach
            <tr class="border-t-2 border-black">
                <td class="py-2 text-left font-semibold" colspan="100%">DPH rekapitulace</td>
            </tr>
            @foreach($bill->vat_rates as $vat)
                <tr>
                    <td class="py-2">Sazba {{ $vat }}</td>
                    <td colspan="6">základ DPH: {{ $formatCurrency($vatSummary[$vat]['base'] ?? 0) }} + DPH: {{ $formatCurrency($vatSummary[$vat]['vat'] ?? 0) }}</td>
                    <td class="text-right">{{ $formatCurrency($vatSummary[$vat]['total'] ?? 0) }}</td>
                </tr>
            @endforeach
            <tr class="border-t-2 border-b-2 border-black">
                <td class="py-2 text-left font-semibold" colspan="7">Celkem k úhradě</td>
                <td class="text-sm font-semibold text-right">{{ $formatCurrency($bill->total_price) }}</td>
            </tr>
            </tbody>
        </table>

    </div>
@endsection

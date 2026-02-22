@extends('bills.base')

@section('title', $bill->number)

@section('document')
    @php
        $formatCurrency = static fn (float $value): string => number_format($value, 2, ',', ' ') . ' Kč';
    @endphp
    <div class="text-[11px] leading-tight text-slate-800">
        <table class="w-full">
            <thead class="border-b border-teal-700">
            <tr class="border-b border-teal-200 bg-teal-50/70">
                <td class="px-1 py-1.5 font-semibold text-teal-900" colspan="2">Účtenka č.</td>
                <td class="px-1 py-1.5 text-right font-semibold text-teal-900" colspan="2">{{ $bill->number }}</td>
            </tr>
            <tr class="border-b border-teal-100">
                <td class="px-1 py-1.5 text-slate-600" colspan="2">Datum a čas</td>
                <td class="px-1 py-1.5 text-right text-slate-700" colspan="2">{{ $bill->created_at?->format('d.m.Y H:i') }}</td>
            </tr>
            <tr class="bg-slate-50">
                <td class="px-1 py-1.5 font-semibold uppercase tracking-wide text-slate-700" colspan="2">Položka</td>
                <td class="px-1 py-1.5 text-right font-semibold uppercase tracking-wide text-slate-700">DPH</td>
                <td class="px-1 py-1.5 text-right font-semibold uppercase tracking-wide text-slate-700">Celkem s DPH</td>
            </tr>
            </thead>
            <tbody>
            @foreach($billItems as $item)
                <tr class="border-t border-slate-100">
                    <td class="px-1 pb-0.5 pt-1.5 text-left font-semibold text-slate-900" colspan="4">{{ $item->order_column }} - {{ $item->name }}</td>
                </tr>
                <tr>
                    <td class="px-1 pb-1.5 text-slate-700">{{ $item->packs }} x {{ $item->quantity }} x {{ $formatCurrency($item->calculated_unit_price) }}</td>
                    <td></td>
                    <td class="px-1 pb-1.5 text-right text-slate-600">{{ $item->vat_rate }}</td>
                    <td class="px-1 pb-1.5 text-right font-medium text-slate-900">{{ $formatCurrency($item->total_price) }}</td>
                </tr>
            @endforeach
            <tr class="border-t-2 border-teal-700">
                <td class="px-1 py-1.5 text-left font-semibold text-teal-800" colspan="100%">DPH rekapitulace</td>
            </tr>
            @foreach($bill->vat_rates as $vat)
                <tr>
                    <td class="px-1 py-1 text-slate-700" colspan="2">Základ DPH {{ $vat }}</td>
                    <td class="px-1 py-1 text-right text-slate-700" colspan="2">{{ $formatCurrency($vatSummary[$vat]['base'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="px-1 py-1 text-slate-700" colspan="2">DPH {{ $vat }}</td>
                    <td class="px-1 py-1 text-right text-slate-700" colspan="2">{{ $formatCurrency($vatSummary[$vat]['vat'] ?? 0) }}</td>
                </tr>
                <tr class="border-b border-slate-100">
                    <td class="px-1 py-1 text-slate-700" colspan="2">Celkem {{ $vat }}</td>
                    <td class="px-1 py-1 text-right font-medium text-slate-800" colspan="2">{{ $formatCurrency($vatSummary[$vat]['total'] ?? 0) }}</td>
                </tr>
            @endforeach
            <tr class="border-y-2 border-teal-700 bg-teal-50/70">
                <td class="px-1 py-1.5 text-left font-semibold text-teal-900">Celkem k úhradě</td>
                <td class="px-1 py-1.5 text-right text-base font-semibold text-teal-900" colspan="3">{{ $formatCurrency($bill->total_price) }}</td>
            </tr>
            </tbody>
        </table>
    </div>
@endsection

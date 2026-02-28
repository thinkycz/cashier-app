@extends('bills.base')

@section('title', $bill->number)

@section('document')
@php
$formatCurrency = static fn (float $value): string => number_format($value, 2, ',', ' ') . ' Kč';
@endphp
<div class="text-[11px] leading-tight text-slate-800">
    <table class="w-full">
        <thead class="border-b border-teal-700">
            <tr>
                <td class="text-center font-semibold text-slate-900" colspan="100%">{{ $bill->supplier->company_name }}</td>
            </tr>
            @if($bill->supplier->full_name)
            <tr>
                <td class="text-center text-slate-700" colspan="100%">{{ $bill->supplier->full_name }}</td>
            </tr>
            @endif
            <tr>
                <td class="text-center text-slate-700" colspan="100%">{{ $bill->supplier->street }}</td>
            </tr>
            <tr>
                <td class="text-center text-slate-700" colspan="100%">{{ $bill->supplier->zip }} {{ $bill->supplier->city }}</td>
            </tr>
            <tr>
                <td class="text-center text-slate-700" colspan="100%">{{ __('Company ID') }}: {{ $bill->supplier->company_id }}</td>
            </tr>
            @if($bill->supplier->vat_id)
            <tr>
                <td class="text-center text-slate-700" colspan="100%">{{ __('VAT ID') }}: {{ $bill->supplier->vat_id }}</td>
            </tr>
            @endif
            <tr class="border-b border-teal-200 bg-teal-50/70">
                <td class="px-1 py-1.5 font-semibold text-teal-900" colspan="2">{{ __('bill.receipt_no') }}</td>
                <td class="px-1 py-1.5 text-right font-semibold text-teal-900" colspan="2">{{ $bill->number }}</td>
            </tr>
            <tr class="border-b border-teal-100">
                <td class="px-1 py-1.5 text-slate-600" colspan="2">{{ __('bill.date_time') }}</td>
                <td class="px-1 py-1.5 text-right text-slate-700" colspan="2">{{ $bill->created_at?->format('d.m.Y H:i') }}</td>
            </tr>
            <tr class="bg-slate-50">
                <td class="px-1 py-1.5 font-semibold uppercase tracking-wide text-slate-700" colspan="2">{{ __('bill.item') }}</td>
                <td class="px-1 py-1.5 text-right font-semibold uppercase tracking-wide text-slate-700">{{ __('bill.vat') }}</td>
                <td class="px-1 py-1.5 text-right font-semibold uppercase tracking-wide text-slate-700">{{ __('bill.total_with_vat') }}</td>
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
                <td class="px-1 py-1.5 text-left font-semibold text-teal-800" colspan="100%">{{ __('bill.vat_recap') }}</td>
            </tr>
            @foreach($bill->vat_rates as $vat)
            <tr>
                <td class="px-1 py-1 text-slate-700" colspan="2">{{ __('bill.vat_base') }} {{ $vat }}</td>
                <td class="px-1 py-1 text-right text-slate-700" colspan="2">{{ $formatCurrency($vatSummary[$vat]['base'] ?? 0) }}</td>
            </tr>
            <tr>
                <td class="px-1 py-1 text-slate-700" colspan="2">{{ __('bill.vat') }} {{ $vat }}</td>
                <td class="px-1 py-1 text-right text-slate-700" colspan="2">{{ $formatCurrency($vatSummary[$vat]['vat'] ?? 0) }}</td>
            </tr>
            <tr class="border-b border-slate-100">
                <td class="px-1 py-1 text-slate-700" colspan="2">{{ __('bill.total') }} {{ $vat }}</td>
                <td class="px-1 py-1 text-right font-medium text-slate-800" colspan="2">{{ $formatCurrency($vatSummary[$vat]['total'] ?? 0) }}</td>
            </tr>
            @endforeach
            <tr class="border-y-2 border-teal-700 bg-teal-50/70">
                <td class="px-1 py-1.5 text-left font-semibold text-teal-900">{{ __('bill.total_to_pay') }}</td>
                <td class="px-1 py-1.5 text-right text-base font-semibold text-teal-900" colspan="3">{{ $formatCurrency($bill->total_price) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
@extends('bills.base')

@section('title', $bill->number)

@section('document')
@php
$formatCurrency = static fn (float $value): string => number_format($value, 2, ',', ' ') . ' Kč';
@endphp
<div class="text-[11px] leading-tight text-slate-800">
    <table class="w-full">
        <thead class="border-b border-teal-700">
            <tr class="border-y-2 border-teal-700 bg-teal-50/70">
                <td class="py-1.5 text-center font-semibold text-teal-900" colspan="100%">{{ __('Order') }}</td>
            </tr>
            <tr class="bg-slate-50">
                <td class="px-1 py-1.5 font-semibold uppercase tracking-wide text-slate-700">{{ __('Item') }}</td>
                <td class="px-1 py-1.5 text-right font-semibold uppercase tracking-wide text-slate-700">{{ __('Total') }}</td>
            </tr>
        </thead>
        <tbody>
            @foreach($billItems as $item)
            <tr class="border-t border-slate-100">
                <td class="px-1 pb-0.5 pt-1.5 text-left font-semibold text-slate-900" colspan="100%">{{ $item->order_column }} - {{ $item->name }}{{ $item->product?->short_name ? ' - ' . $item->product?->short_name : '' }}</td>
            </tr>
            <tr>
                <td class="px-1 pb-1.5 text-slate-700">{{ $item->packs }} x {{ $item->quantity }} x {{ $formatCurrency($item->calculated_unit_price) }}</td>
                <td class="px-1 pb-1.5 text-right font-medium text-slate-900">= {{ $formatCurrency($item->total_price) }}</td>
            </tr>
            @endforeach
            <tr class="border-y-2 border-teal-700 bg-teal-50/70">
                <td class="px-1 py-1.5 text-left font-semibold text-teal-900">{{ __('Total to pay') }}</td>
                <td class="px-1 py-1.5 text-right text-base font-semibold text-teal-900">{{ $formatCurrency($bill->total_price) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
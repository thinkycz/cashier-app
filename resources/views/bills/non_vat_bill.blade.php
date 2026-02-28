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
                <td class="text-center text-slate-700" colspan="100%">IČ: {{ $bill->supplier->company_id }}</td>
            </tr>

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
                <td class="px-1 py-1.5 text-right font-semibold uppercase tracking-wide text-slate-700" colspan="2">Celkem</td>
            </tr>
        </thead>
        <tbody>
            @foreach($billItems as $item)
            <tr class="border-t border-slate-100">
                <td class="px-1 pb-0.5 pt-1.5 text-left font-semibold text-slate-900" colspan="4">{{ $item->order_column }} - {{ $item->name }}</td>
            </tr>
            <tr>
                <td class="px-1 pb-1.5 text-slate-700" colspan="2">{{ $item->packs }} x {{ $item->quantity }} x {{ $formatCurrency($item->calculated_unit_price) }}</td>
                <td class="px-1 pb-1.5 text-right font-medium text-slate-900" colspan="2">{{ $formatCurrency($item->total_price) }}</td>
            </tr>
            @endforeach
            <tr class="border-y-2 border-teal-700 bg-teal-50/70">
                <td class="px-1 py-1.5 text-left font-semibold text-teal-900" colspan="2">Celkem k úhradě</td>
                <td class="px-1 py-1.5 text-right text-base font-semibold text-teal-900" colspan="2">{{ $formatCurrency($bill->total_price) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
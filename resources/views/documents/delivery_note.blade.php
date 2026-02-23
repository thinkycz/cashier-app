@extends('documents.base')

@section('title', $bill->number)

@section('document')
    @php
        $hasCustomer = (bool) ($bill->customer->company_name
            || $bill->customer->full_name
            || $bill->customer->street
            || $bill->customer->zip
            || $bill->customer->city);
    @endphp

    <div class="text-xs">
        <div class="flex justify-between pb-4 border-b-2 border-black mb-6">
            <div class="flex flex-col">
                <p class="text-lg font-semibold">Dodací list</p>
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
                <p>Datum zdanitelného plnění: {{ $bill->created_at?->format('d.m.Y') }}</p>
                <p>Datum splatnosti: {{ $bill->created_at?->copy()?->addDays(7)?->format('d.m.Y') }}</p>
                <p>Číslo účtu: {{ $bill->supplier->bank_account ?: '-' }}</p>
                <p>Variabilní symbol: {{ isset($bill->id) ? str_pad((string) $bill->id, 10, '0', STR_PAD_LEFT) : '-' }}</p>
            </div>
        </div>

        <table class="w-full">
            <thead class="border-b border-black">
            <tr>
                <td class="py-2 text-left font-semibold">#</td>
                <td class="text-left font-semibold">Název</td>
                <td class="text-left font-semibold">Katalog</td>
                <td class="text-left font-semibold">EAN</td>
                <td class="text-right font-semibold">Počet</td>
            </tr>
            </thead>
            <tbody>
            @foreach($billItems as $item)
                <tr>
                    <td class="py-2 text-left">{{ $item->order_column }}</td>
                    <td class="text-left">{{ $item->name }}</td>
                    <td class="text-left">{{ $item->product?->short_name ?: '-' }}</td>
                    <td class="text-left">{{ $item->product?->ean ?: '-' }}</td>
                    <td class="text-right">{{ $item->packs }} x {{ $item->quantity }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection

<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('company_id', 'like', "%{$search}%")
                    ->orWhere('vat_id', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('company_name')->paginate(10);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Customers/Form', [
            'mode' => 'create',
            'customer' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);
        $validated['country_code'] = strtoupper($validated['country_code']);

        Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer was successfully created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        return Inertia::render('Customers/Show', [
            'customer' => $customer,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return Inertia::render('Customers/Form', [
            'mode' => 'edit',
            'customer' => $customer,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $this->validatePayload($request, $customer->id);
        $validated['country_code'] = strtoupper($validated['country_code']);

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer was successfully updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer was successfully deleted.');
    }

    private function validatePayload(Request $request, ?int $customerId = null): array
    {
        return $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'string', 'max:255', 'unique:customers,company_id,' . ($customerId ?? 'NULL')],
            'vat_id' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:50'],
            'country_code' => ['required', 'string', 'size:2'],
        ]);
    }
}

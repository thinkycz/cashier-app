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
        $query = Customer::query()->where('user_id', $request->user()->id);

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
        $validated = $this->normalizePayload($this->validatePayload($request));

        Customer::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

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
        $validated = $this->normalizePayload($this->validatePayload($request));

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

    private function validatePayload(Request $request): array
    {
        $input = $request->all();

        foreach ([
            'company_name',
            'company_id',
            'vat_id',
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'street',
            'city',
            'zip',
            'country_code',
        ] as $field) {
            if (! array_key_exists($field, $input) || ! is_string($input[$field])) {
                continue;
            }

            $trimmed = trim($input[$field]);
            $input[$field] = $trimmed === '' ? null : $trimmed;
        }

        return validator($input, [
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'string', 'max:255'],
            'vat_id' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:50'],
            'country_code' => ['nullable', 'string', 'size:2'],
        ])->validate();
    }

    private function normalizePayload(array $validated): array
    {
        if (isset($validated['country_code']) && is_string($validated['country_code'])) {
            $validated['country_code'] = strtoupper($validated['country_code']);
        }

        return $validated;
    }
}

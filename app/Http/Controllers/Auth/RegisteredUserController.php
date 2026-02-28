<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_id' => 'required|string|max:255|unique:' . User::class . ',company_id',
            'company_name' => 'nullable|string|max:255',
            'vat_id' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:255',
            'country_code' => 'nullable|string|size:2',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_vat_payer' => 'boolean',
            'subject_type' => 'nullable|string|in:fyzicka osoba,pravnicka osoba',
        ]);

        $normalizeNullableString = static function (mixed $value): ?string {
            if (! is_string($value)) {
                return null;
            }

            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        };

        $user = User::create([
            'first_name' => $normalizeNullableString($validated['first_name']),
            'last_name' => $normalizeNullableString($validated['last_name']),
            'company_id' => $normalizeNullableString($validated['company_id']),
            'company_name' => $normalizeNullableString($validated['company_name'] ?? null),
            'vat_id' => $normalizeNullableString($validated['vat_id'] ?? null),
            'street' => $normalizeNullableString($validated['street'] ?? null),
            'city' => $normalizeNullableString($validated['city'] ?? null),
            'zip' => $normalizeNullableString($validated['zip'] ?? null),
            'country_code' => $normalizeNullableString($validated['country_code'] ?? null),
            'email' => $normalizeNullableString($validated['email']),
            'is_vat_payer' => $validated['is_vat_payer'] ?? false,
            'subject_type' => $normalizeNullableString($validated['subject_type'] ?? null),
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}

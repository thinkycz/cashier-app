<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_name' => $this->normalizeNullableString('company_name'),
            'company_id' => $this->normalizeNullableString('company_id'),
            'vat_id' => $this->normalizeNullableString('vat_id'),
            'bank_account' => $this->normalizeNullableString('bank_account'),
            'first_name' => $this->normalizeNullableString('first_name'),
            'last_name' => $this->normalizeNullableString('last_name'),
            'phone_number' => $this->normalizeNullableString('phone_number'),
            'street' => $this->normalizeNullableString('street'),
            'city' => $this->normalizeNullableString('city'),
            'zip' => $this->normalizeNullableString('zip'),
            'country_code' => $this->normalizeNullableString('country_code'),
            'is_vat_payer' => $this->boolean('is_vat_payer'),
            'subject_type' => $this->normalizeNullableString('subject_type'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique(User::class, 'company_id')->ignore($this->user()->id),
            ],
            'vat_id' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'is_vat_payer' => ['boolean'],
            'subject_type' => ['nullable', 'string', 'in:fyzicka osoba,pravnicka osoba'],
        ];
    }

    private function normalizeNullableString(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}

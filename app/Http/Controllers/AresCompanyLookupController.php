<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AresCompanyLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'string', 'max:255'],
        ]);

        $ico = preg_replace('/\D+/', '', $validated['company_id']) ?? '';

        if (! preg_match('/^\d{8}$/', $ico)) {
            throw ValidationException::withMessages([
                'company_id' => 'Company ID must be 8 digits.',
            ]);
        }

        $payload = Cache::remember(
            "ares:company:{$ico}",
            now()->addDay(),
            fn () => $this->fetchCompanyPayload($ico),
        );

        if (! $payload) {
            return response()->json([
                'message' => 'Company not found.',
            ], 404);
        }

        return response()->json($this->mapCompanyPayload($payload));
    }

    private function fetchCompanyPayload(string $ico): ?array
    {
        $response = Http::timeout(10)
            ->acceptJson()
            ->get("https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/{$ico}");

        if ($response->status() === 404) {
            return null;
        }

        return $response->throw()->json();
    }

    private function mapCompanyPayload(array $payload): array
    {
        $sidlo = is_array($payload['sidlo'] ?? null) ? $payload['sidlo'] : [];

        return [
            'company_name' => $this->normalizeNullableString($payload['obchodniJmeno'] ?? null),
            'company_id' => $this->normalizeNullableString($payload['ico'] ?? null),
            'vat_id' => $this->normalizeNullableString($payload['dic'] ?? null),
            'street' => $this->normalizeNullableString($this->formatStreet($sidlo)),
            'city' => $this->normalizeNullableString($sidlo['nazevSpravnihoObvodu'] ?? $sidlo['nazevObce'] ?? null),
            'zip' => $this->formatZip($sidlo['psc'] ?? null),
            'country_code' => $this->normalizeNullableString($sidlo['kodStatu'] ?? null),
        ];
    }

    private function formatStreet(array $sidlo): ?string
    {
        $street = $this->normalizeNullableString($sidlo['nazevUlice'] ?? null);

        if ($street) {
            $houseNumber = $this->normalizeNullableString($sidlo['cisloDomovni'] ?? null);
            $orientNumber = $this->normalizeNullableString($sidlo['cisloOrientacni'] ?? null);
            $orientSuffix = $this->normalizeNullableString($sidlo['cisloOrientacniPismeno'] ?? null);

            $number = null;

            if ($houseNumber && $orientNumber) {
                $number = $houseNumber.'/'.$orientNumber.($orientSuffix ?? '');
            } elseif ($houseNumber) {
                $number = $houseNumber;
            } elseif ($orientNumber) {
                $number = $orientNumber.($orientSuffix ?? '');
            }

            return trim($street.' '.($number ?? ''));
        }

        $textAddress = $this->normalizeNullableString($sidlo['textovaAdresa'] ?? null);

        if (! $textAddress) {
            return null;
        }

        $firstPart = trim((string) (explode(',', $textAddress, 2)[0] ?? ''));

        return $firstPart !== '' ? $firstPart : null;
    }

    private function formatZip(mixed $zip): ?string
    {
        if (is_int($zip)) {
            return str_pad((string) $zip, 5, '0', STR_PAD_LEFT);
        }

        if (is_string($zip)) {
            $trimmed = trim($zip);
            return $trimmed === '' ? null : $trimmed;
        }

        return null;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}


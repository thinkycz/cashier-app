<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AresService
{
    protected const BASE_URL = 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/';

    public static function find(string $ico): array
    {
        $payload = Cache::remember(
            "ares:company:{$ico}",
            now()->addDay(),
            fn() => self::fetchCompanyPayload($ico),
        );

        if (! $payload) {
            return [];
        }

        return self::mapCompanyPayload($payload);
    }

    private static function fetchCompanyPayload(string $ico): ?array
    {
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get(self::BASE_URL . $ico);
        } catch (Exception $e) {
            throw new RuntimeException(__('messages.ares.service_not_available'));
        }

        if ($response->status() === 404) {
            return null;
        }

        return $response->throw()->json();
    }

    private static function mapCompanyPayload(array $payload): array
    {
        $sidlo = is_array($payload['sidlo'] ?? null) ? $payload['sidlo'] : [];

        $pravniForma = self::normalizeNullableString($payload['pravniForma'] ?? null);
        $subjectType = null;
        if ($pravniForma) {
            $subjectType = str_starts_with($pravniForma, '10') ? 'fyzicka osoba' : 'pravnicka osoba';
        }

        return [
            'company_name' => self::normalizeNullableString($payload['obchodniJmeno'] ?? null),
            'company_id' => self::normalizeNullableString($payload['ico'] ?? null),
            'vat_id' => self::normalizeNullableString($payload['dic'] ?? null),
            'is_vat_payer' => !empty($payload['dic']),
            'subject_type' => $subjectType,
            'street' => self::normalizeNullableString(self::formatStreet($sidlo)),
            'city' => self::normalizeNullableString($sidlo['nazevSpravnihoObvodu'] ?? $sidlo['nazevObce'] ?? null),
            'zip' => self::formatZip($sidlo['psc'] ?? null),
            'country_code' => self::normalizeNullableString($sidlo['kodStatu'] ?? null),
        ];
    }

    private static function formatStreet(array $sidlo): ?string
    {
        $street = self::normalizeNullableString($sidlo['nazevUlice'] ?? null);

        if ($street) {
            $houseNumber = self::normalizeNullableString($sidlo['cisloDomovni'] ?? null);
            $orientNumber = self::normalizeNullableString($sidlo['cisloOrientacni'] ?? null);
            $orientSuffix = self::normalizeNullableString($sidlo['cisloOrientacniPismeno'] ?? null);

            $number = null;

            if ($houseNumber && $orientNumber) {
                $number = $houseNumber . '/' . $orientNumber . ($orientSuffix ?? '');
            } elseif ($houseNumber) {
                $number = $houseNumber;
            } elseif ($orientNumber) {
                $number = $orientNumber . ($orientSuffix ?? '');
            }

            return trim($street . ' ' . ($number ?? ''));
        }

        $textAddress = self::normalizeNullableString($sidlo['textovaAdresa'] ?? null);

        if (! $textAddress) {
            return null;
        }

        $firstPart = trim((string) (explode(',', $textAddress, 2)[0] ?? ''));

        return $firstPart !== '' ? $firstPart : null;
    }

    private static function formatZip(mixed $zip): ?string
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

    private static function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}

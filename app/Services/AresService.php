<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AresService
{
    protected const BASE_URL = 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/';

    public static function find(string $ico): array
    {
        try {
            $response = Http::acceptJson()->get(self::BASE_URL . $ico);
        } catch (Exception $e) {
            throw new RuntimeException(__('messages.ares.service_not_available'));
        }

        $result = $response->json();

        return [
            'company_id'  => $ico,
            'vat_id' => Arr::get($result, 'dic'),
            'company_name'    => Arr::get($result, 'obchodniJmeno'),
            'street'     => Str::of(Arr::get($result, 'sidlo.textovaAdresa'))->before(',')->toString(),
            'city'       => Arr::get($result, 'sidlo.nazevObce'),
            'zip'   => Arr::get($result, 'sidlo.psc'),
            'country_code' => Arr::get($result, 'sidlo.kodStatu')
        ];
    }
}

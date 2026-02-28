<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AresCompanyLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_id_must_be_8_digits(): void
    {
        Cache::flush();

        $response = $this->getJson('/ares/company?company_id=ACME-001');

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('company_id');
    }

    public function test_lookup_returns_mapped_company_data(): void
    {
        Cache::flush();

        Http::fake([
            'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/27074358' => Http::response([
                'ico' => '27074358',
                'obchodniJmeno' => 'Asseco Central Europe, a.s.',
                'dic' => 'CZ27074358',
                'sidlo' => [
                    'kodStatu' => 'CZ',
                    'nazevObce' => 'Praha',
                    'nazevSpravnihoObvodu' => 'Praha 4',
                    'nazevUlice' => 'Budějovická',
                    'cisloDomovni' => 778,
                    'cisloOrientacni' => 3,
                    'cisloOrientacniPismeno' => 'a',
                    'psc' => 14000,
                    'textovaAdresa' => 'Budějovická 778/3a, Michle, 14000 Praha 4',
                ],
            ], 200),
        ]);

        $response = $this->getJson('/ares/company?company_id=27074358');

        $response
            ->assertOk()
            ->assertJson([
                'company_name' => 'Asseco Central Europe, a.s.',
                'company_id' => '27074358',
                'vat_id' => 'CZ27074358',
                'street' => 'Budějovická 778/3a',
                'city' => 'Praha 4',
                'zip' => '14000',
                'country_code' => 'CZ',
            ]);
    }

    public function test_lookup_returns_404_when_company_missing(): void
    {
        Cache::flush();

        Http::fake([
            'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/12345678' => Http::response([], 404),
        ]);

        $response = $this->getJson('/ares/company?company_id=12345678');

        $response
            ->assertStatus(404)
            ->assertJson([
                'message' => 'Company not found.',
            ]);
    }
}


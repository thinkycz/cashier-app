<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\User;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoUser = User::where('email', 'testovaci@admin.cz')->first();

        if (! $demoUser) {
            return;
        }

        $products = [
            [
                'name' => 'nuoc hoa 100ml',
                'short_name' => 'nuoc hoa',
                'ean' => '1234567890123',
                'vat_rate' => 21.00,
                'price' => 599.99,
                'is_active' => true,
            ],
            [
                'name' => 'nuoc hoa du bai 50ml',
                'short_name' => 'du bai',
                'ean' => '1234567890124',
                'vat_rate' => 21.00,
                'price' => 299.99,
                'is_active' => true,
            ],
            [
                'name' => 'Rukavice',
                'short_name' => 'rukavice',
                'ean' => '1234567890125',
                'vat_rate' => 21.00,
                'price' => 49.99,
                'is_active' => true,
            ],
            [
                'name' => 'Izolepa',
                'short_name' => 'izolepa',
                'ean' => '1234567890126',
                'vat_rate' => 21.00,
                'price' => 79.99,
                'is_active' => true,
            ],
            [
                'name' => 'Obraz z kaminků',
                'short_name' => 'obraz',
                'ean' => '1234567890127',
                'vat_rate' => 21.00,
                'price' => 1299.99,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                [
                    'user_id' => $demoUser->id,
                    'ean' => $product['ean'],
                ],
                [
                    ...$product,
                    'user_id' => $demoUser->id,
                ]
            );
        }
    }
}

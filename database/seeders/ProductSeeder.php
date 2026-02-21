<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'nuoc hoa 100ml',
                'category' => 'Parfém',
                'ean' => '1234567890123',
                'vat_rate' => 21.00,
                'price' => 599.99,
                'description' => 'Vietnamský parfém 100ml',
                'is_active' => true,
            ],
            [
                'name' => 'nuoc hoa du bai 50ml',
                'category' => 'Parfém',
                'ean' => '1234567890124',
                'vat_rate' => 21.00,
                'price' => 299.99,
                'description' => 'Vietnamský parfém z Dubaje 50ml',
                'is_active' => true,
            ],
            [
                'name' => 'Rukavice',
                'category' => 'Ochranné pomůcky',
                'ean' => '1234567890125',
                'vat_rate' => 21.00,
                'price' => 49.99,
                'description' => 'Jednorázové rukavice',
                'is_active' => true,
            ],
            [
                'name' => 'Izolepa',
                'category' => 'Kancelářské potřeby',
                'ean' => '1234567890126',
                'vat_rate' => 21.00,
                'price' => 79.99,
                'description' => 'Lepicí páska',
                'is_active' => true,
            ],
            [
                'name' => 'Obraz z kaminků',
                'category' => 'Dekorace',
                'ean' => '1234567890127',
                'vat_rate' => 21.00,
                'price' => 1299.99,
                'description' => 'Ručně vyráběný obraz z kamínků',
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}

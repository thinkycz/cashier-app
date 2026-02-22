<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate([
            'email' => 'testovaci@admin.cz',
        ], [
            'first_name' => 'Testovaci',
            'last_name' => 'Admin',
            'company_id' => 'ADMIN-001',
            'password' => Hash::make('password'),
        ]);

        $this->call([
            ProductSeeder::class,
        ]);
    }
}

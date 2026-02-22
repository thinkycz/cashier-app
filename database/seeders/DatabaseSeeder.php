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
            'name' => 'Testovaci Admin',
            'password' => Hash::make('password'),
        ]);

        $this->call([
            ProductSeeder::class,
        ]);
    }
}

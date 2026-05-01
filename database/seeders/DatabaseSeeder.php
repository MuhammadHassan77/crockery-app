<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name'     => 'Admin',
            'email'    => 'admin@bartanshop.com',
            'password' => bcrypt('admin123'),
            'is_admin' => true,
            'is_active'=> true,
        ]);

        User::factory()->create([
            'name'     => 'Staff',
            'email'    => 'staff@bartanshop.com',
            'password' => bcrypt('staff123'),
            'is_admin' => false,
            'is_active'=> true,
        ]);
    }
}

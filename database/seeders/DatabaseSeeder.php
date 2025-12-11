<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create two driver test users
        User::factory()->driver()->create([
            'name' => 'Driver One',
            'email' => 'driver1@zip.test',
            'password' => 'password',
        ]);

        User::factory()->driver()->create([
            'name' => 'Driver Two',
            'email' => 'driver2@zip.test',
            'password' => 'password',
        ]);

        // Create supervisor
        $this->call(SupervisorSeeder::class);
    }
}

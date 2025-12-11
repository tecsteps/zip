<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SupervisorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->supervisor()->create([
            'name' => 'Supervisor',
            'email' => 'supervisor@zip.test',
            'password' => 'password',
        ]);
    }
}

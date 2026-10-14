<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo credentials only; documented in the README.
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@domainvault.test',
            'password' => 'password',
        ])->forceFill(['role' => UserRole::Admin])->save();

        User::factory()->create([
            'name' => 'Buyer User',
            'email' => 'buyer@domainvault.test',
            'password' => 'password',
        ]);

        $this->call(DomainSeeder::class);
    }
}

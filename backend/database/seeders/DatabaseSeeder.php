<?php

namespace Database\Seeders;

use App\Models\Data;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'name' => 'Admin User',
            'npp' => '1001',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Create User
        $user = User::create([
            'name' => 'Regular User',
            'npp' => '1002',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        // Create Sample Data
        $categories = ['Sales', 'Marketing', 'Operations', 'Finance', 'IT'];
        $startDate = now()->subDays(90);

        for ($i = 0; $i < 50; $i++) {
            Data::create([
                'category' => $categories[array_rand($categories)],
                'value' => rand(1000, 50000) / 100,
                'date' => $startDate->copy()->addDays(rand(0, 90)),
                'title' => 'Sample Data Entry ' . ($i + 1),
                'description' => 'This is a sample data entry for testing purposes.',
                'status' => 'active',
                'metadata' => [
                    'source' => 'seed',
                    'priority' => ['low', 'medium', 'high'][rand(0, 2)],
                ],
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin NPP: 1001 / password123');
        $this->command->info('User NPP: 1002 / password123');
    }
}

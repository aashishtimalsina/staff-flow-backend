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
        // Super Admin
        User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@staffflow.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_SUPERADMIN,
            'is_active' => true,
            'is_verified' => true,
            'phone' => '+44 20 7946 0958',
            'address' => '10 Downing Street',
            'city' => 'London',
            'state' => User::STATE_ENGLAND,
            'postal_code' => 'SW1A 2AA',
        ]);

        // Admin Users
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@staffflow.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'is_verified' => true,
            'phone' => '+44 131 242 7200',
            'address' => '42 Haddington Place',
            'city' => 'Edinburgh',
            'state' => User::STATE_SCOTLAND,
            'postal_code' => 'EH7 4AL',
        ]);

        User::create([
            'name' => 'Administrator Wales',
            'email' => 'admin.wales@staffflow.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'is_verified' => true,
            'phone' => '+44 29 2039 0390',
            'address' => 'City Hall, The Hayes',
            'city' => 'Cardiff',
            'state' => User::STATE_WALES,
            'postal_code' => 'CF10 1AH',
        ]);

        // Regular Users
        User::create([
            'name' => 'John Smith',
            'email' => 'john.smith@staffflow.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_USER,
            'is_active' => true,
            'is_verified' => true,
            'phone' => '+44 20 8748 8000',
            'address' => '123 Oxford Street',
            'city' => 'London',
            'state' => User::STATE_ENGLAND,
            'postal_code' => 'W1D 2HD',
        ]);

        User::create([
            'name' => 'Sarah Johnson',
            'email' => 'sarah.johnson@staffflow.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_USER,
            'is_active' => true,
            'is_verified' => false,
            'phone' => '+44 141 222 9600',
            'address' => '50 Renfrew Street',
            'city' => 'Glasgow',
            'state' => User::STATE_SCOTLAND,
            'postal_code' => 'G2 3DB',
        ]);

        User::create([
            'name' => 'Emma Williams',
            'email' => 'emma.williams@staffflow.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_USER,
            'is_active' => true,
            'is_verified' => true,
            'phone' => '+44 292 039 0390',
            'address' => '1 Museum Place',
            'city' => 'Cardiff',
            'state' => User::STATE_WALES,
            'postal_code' => 'CF10 3BD',
        ]);

        User::create([
            'name' => 'Michael Brown',
            'email' => 'michael.brown@staffflow.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_USER,
            'is_active' => false,
            'is_verified' => true,
            'phone' => '+44 289 032 7000',
            'address' => '4 Linenhall Street',
            'city' => 'Belfast',
            'state' => User::STATE_NORTHERN_IRELAND,
            'postal_code' => 'BT2 8AA',
        ]);

        // Test user for development
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_USER,
            'is_active' => true,
            'is_verified' => false,
            'phone' => '+44 20 7946 0958',
            'address' => '221B Baker Street',
            'city' => 'London',
            'state' => User::STATE_ENGLAND,
            'postal_code' => 'NW1 6XE',
        ]);
    }
}

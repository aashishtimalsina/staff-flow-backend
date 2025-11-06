<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
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
            'phone' => '+44 20 7946 0958',
        ]);

        // Admin Users
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@staffflow.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'phone' => '+44 131 242 7200',
        ]);

        User::create([
            'name' => 'Recruiter User',
            'email' => 'recruiter@staffflow.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_RECRUITER,
            'is_active' => true,
            'phone' => '+44 29 2039 0390',
        ]);

        // Finance User
        User::create([
            'name' => 'Finance Manager',
            'email' => 'finance@staffflow.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_FINANCE,
            'is_active' => true,
            'phone' => '+44 20 8748 8000',
        ]);

        // Compliance User
        User::create([
            'name' => 'Compliance Officer',
            'email' => 'compliance@staffflow.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_COMPLIANCE,
            'is_active' => true,
            'phone' => '+44 141 222 9600',
        ]);

        // Worker Users
        User::create([
            'name' => 'John Worker',
            'email' => 'worker1@staffflow.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_WORKER,
            'is_active' => true,
            'phone' => '+44 292 039 0390',
        ]);

        User::create([
            'name' => 'Jane Worker',
            'email' => 'worker2@staffflow.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_WORKER,
            'is_active' => true,
            'phone' => '+44 289 032 7000',
        ]);

        // Test user for development
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'phone' => '+44 20 7946 0958',
        ]);
    }
}

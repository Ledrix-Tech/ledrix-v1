<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LedrixSuperAdmin extends Seeder
{
    public function run(): void
    {
        // Only seed if no super admins exist
        // Safe to run multiple times
        $exists = DB::connection('central')
                    ->table('super_admins')
                    ->exists();

        if ($exists) {
            $this->command->warn('Super admins already exist. Skipping.');
            return;
        }

        DB::connection('central')->table('super_admins')->insert([
            'name'       => env('SUPER_ADMIN_NAME', 'Ledrix Owner'),
            'email'      => env('SUPER_ADMIN_EMAIL', 'owner@ledrix.app'),
            'password'   => Hash::make(env('SUPER_ADMIN_PASSWORD', 'Ledrix@123')),
            'role'       => 'owner',
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('✅ Super admin created: ' . env('SUPER_ADMIN_EMAIL', 'owner@ledrix.co'));
        $this->command->warn('⚠️  Change the password immediately after first login.');
    }
}
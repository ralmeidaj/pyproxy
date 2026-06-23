<?php

namespace Database\Seeders;

use App\Models\BackofficeUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BackofficeUserSeeder extends Seeder
{
    public function run(): void
    {
        BackofficeUser::firstOrCreate(
            ['email' => 'admin@payproxy.com.br'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('Payproxy@2026!'),
                'role'     => 'super_admin',
            ],
        );
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankPartnerSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('bank_partners')->upsert([
            [
                'name'     => 'PJBank',
                'slug'     => 'pjbank',
                'type'     => 'fintech',
                'status'   => 'active',
                'features' => json_encode([
                    'boleto'        => true,
                    'split'         => true,
                    'dda'           => true,
                    'pix_qr_code'   => true,
                ]),
                'base_url'     => 'https://api.pjbank.com.br',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ], ['slug'], ['name', 'type', 'status', 'features', 'base_url', 'updated_at']);
    }
}

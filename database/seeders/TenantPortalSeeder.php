<?php

namespace Database\Seeders;

use App\Models\BoletoConfig;
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Database\Seeder;

class TenantPortalSeeder extends Seeder
{
    public function run(): void
    {
        // Tenant de teste
        $tenant = Tenant::updateOrCreate(
            ['document' => '13927801000149'],
            [
                'name'                => 'SEFAZ Salvador (Teste)',
                'email'               => 'contato@sefaz.ba.gov.br',
                'phone'               => '(71) 3202-0000',
                'status'              => 'active',
                'communication_model' => 'email',
                'notes'               => 'Tenant de desenvolvimento criado pelo TenantPortalSeeder.',
            ]
        );

        $this->command->info("Tenant: {$tenant->name} (ID {$tenant->id})");

        // Configuração de boleto padrão
        BoletoConfig::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Config Padrão (Teste)'],
            [
                'bank_partner_id'            => \App\Models\BankPartner::where('slug', 'pjbank')->value('id'),
                'is_default'                 => true,
                'status'                     => 'active',
                'prazo_vencimento_dias'       => 30,
                'multa_percentual'           => 2.00,
                'juros_percentual_mes'       => 1.00,
                'desconto_percentual'        => 0,
                'desconto_antecedencia_dias' => 0,
                'instrucoes'                 => 'Não receber após o vencimento.',
                'webhook_url'                => null,
            ]
        );

        $this->command->info('BoletoConfig padrão criada.');

        // Usuários do portal
        $users = [
            [
                'email' => 'admin@sefaz.ba.gov.br',
                'name'  => 'Admin Portal',
                'role'  => 'admin',
            ],
            [
                'email' => 'operador@sefaz.ba.gov.br',
                'name'  => 'Operador Portal',
                'role'  => 'operator',
            ],
            [
                'email' => 'viewer@sefaz.ba.gov.br',
                'name'  => 'Visualizador Portal',
                'role'  => 'viewer',
            ],
        ];

        foreach ($users as $userData) {
            TenantUser::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'tenant_id' => $tenant->id,
                    'name'      => $userData['name'],
                    'password'  => bcrypt('Senha@123'),
                    'role'      => $userData['role'],
                    'active'    => true,
                ]
            );

            $this->command->info("  Usuário: {$userData['email']} [{$userData['role']}] — senha: Senha@123");
        }
    }
}

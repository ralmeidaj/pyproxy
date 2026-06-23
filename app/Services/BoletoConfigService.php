<?php

namespace App\Services;

use App\DTOs\CreateBoletoConfigData;
use App\Models\BoletoConfig;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class BoletoConfigService
{
    public function __construct(private readonly CryptoService $crypto) {}

    public function create(Tenant $tenant, CreateBoletoConfigData $data): BoletoConfig
    {
        return DB::transaction(function () use ($tenant, $data): BoletoConfig {
            if ($data->isDefault) {
                $tenant->boletoConfigs()->update(['is_default' => false]);
            }

            $credentials = json_encode([
                'api_key' => $data->credentialApiKey,
                'chave'   => $data->credentialChave,
            ]);

            return BoletoConfig::create([
                'tenant_id'                  => $tenant->id,
                'bank_partner_id'            => $data->bankPartnerId,
                'name'                       => $data->name,
                'is_default'                 => $data->isDefault,
                'credentials_encrypted'      => $this->crypto->encrypt($credentials),
                'prazo_vencimento_dias'      => $data->prazoVencimentoDias,
                'multa_percentual'           => $data->multaPercentual,
                'juros_percentual_mes'       => $data->jurosPercentualMes,
                'desconto_percentual'        => $data->descontoPercentual,
                'desconto_antecedencia_dias' => $data->descontoAntecedenciaDias,
                'instrucoes'                 => $data->instrucoes,
                'webhook_url'                => $data->webhookUrl,
                'webhook_secret_encrypted'   => $data->webhookSecret
                    ? $this->crypto->encrypt($data->webhookSecret)
                    : null,
            ]);
        });
    }

    public function update(BoletoConfig $config, CreateBoletoConfigData $data): BoletoConfig
    {
        return DB::transaction(function () use ($config, $data): BoletoConfig {
            if ($data->isDefault && ! $config->is_default) {
                $config->tenant->boletoConfigs()->where('id', '!=', $config->id)->update(['is_default' => false]);
            }

            $credentials = json_encode([
                'api_key' => $data->credentialApiKey,
                'chave'   => $data->credentialChave,
            ]);

            $config->update([
                'bank_partner_id'            => $data->bankPartnerId,
                'name'                       => $data->name,
                'is_default'                 => $data->isDefault,
                'credentials_encrypted'      => $this->crypto->encrypt($credentials),
                'prazo_vencimento_dias'      => $data->prazoVencimentoDias,
                'multa_percentual'           => $data->multaPercentual,
                'juros_percentual_mes'       => $data->jurosPercentualMes,
                'desconto_percentual'        => $data->descontoPercentual,
                'desconto_antecedencia_dias' => $data->descontoAntecedenciaDias,
                'instrucoes'                 => $data->instrucoes,
                'webhook_url'                => $data->webhookUrl,
                'webhook_secret_encrypted'   => $data->webhookSecret
                    ? $this->crypto->encrypt($data->webhookSecret)
                    : null,
            ]);

            return $config->fresh();
        });
    }
}

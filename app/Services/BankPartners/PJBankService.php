<?php

namespace App\Services\BankPartners;

use App\Contracts\BankPartnerInterface;
use App\DTOs\BankBoletoResult;
use App\DTOs\IssueBoletoData;
use App\Exceptions\BankPartnerException;
use App\Models\BoletoConfig;
use Illuminate\Support\Facades\Http;

class PJBankService implements BankPartnerInterface
{
    public function issueBoleto(IssueBoletoData $data, BoletoConfig $config, array $splits = []): BankBoletoResult
    {
        $creds      = $config->getCredentials();
        $credencial = $creds['api_key'];
        $chave      = $creds['chave'];
        $hasSplit   = ! empty($splits);

        $payload = [
            'vencimento'          => \Carbon\Carbon::parse($data->dueDate)->format('m/d/Y'),
            'valor'               => number_format($data->amountCents / 100, 2, '.', ''),
            'juros'               => (float) $config->juros_percentual_mes,
            'multa'               => (float) $config->multa_percentual,
            'desconto'            => (float) ($config->desconto_percentual ?? 0),
            'pedido_numero'       => $data->externalRef,
            'nome_cliente'        => $data->payerName,
            'cpf_cliente'         => preg_replace('/\D/', '', $data->payerDocument),
            'email_cliente'       => $data->payerEmail ?? '',
            'endereco_cliente'    => $data->payerAddress['logradouro'] ?? '',
            'numero_cliente'      => $data->payerAddress['numero'] ?? '',
            'complemento_cliente' => $data->payerAddress['complemento'] ?? '',
            'bairro_cliente'      => $data->payerAddress['bairro'] ?? '',
            'cidade_cliente'      => $data->payerAddress['cidade'] ?? '',
            'estado_cliente'      => $data->payerAddress['estado'] ?? '',
            'cep_cliente'         => preg_replace('/\D/', '', $data->payerAddress['cep'] ?? ''),
            'pix'                 => 'pix-e-boleto',
            'instrucao_1'         => $config->instrucoes[0] ?? '',
            'instrucao_2'         => $config->instrucoes[1] ?? '',
            'instrucao_adicional' => $config->instrucoes[2] ?? '',
            'grupo'               => 'Boletos',
        ];

        $pjbankWebhookUrl = config('services.pjbank.webhook_url');
        if (! empty($pjbankWebhookUrl)) {
            $payload['webhook'] = $pjbankWebhookUrl;
        }

        if ($hasSplit) {
            $payload['split'] = array_values(array_map(function (array $split): array {
                $details = $split['payee_details'] ?? [];

                return [
                    'nome'                 => $details['nome'] ?? $split['name'],
                    'cnpj'                 => preg_replace('/\D/', '', $details['cnpj'] ?? ''),
                    'banco_repasse'        => $details['banco_repasse'] ?? '',
                    'agencia_repasse'      => $details['agencia_repasse'] ?? '',
                    'conta_repasse'        => $details['conta_repasse'] ?? '',
                    'valor_fixo'           => round($split['amount_cents'] / 100, 2),
                    'porcentagem_encargos' => $details['porcentagem_encargos'] ?? 0,
                ];
            }, $splits));
        }

        $response = Http::withHeaders(['x-chave' => $chave])
            ->asJson()
            ->post("{$config->bankPartner->base_url}/recebimentos/{$credencial}/transacoes", $payload);

        if ($response->failed()) {
            throw new BankPartnerException(
                "PJBank: falha na emissao — HTTP {$response->status()}: " . $response->body()
            );
        }

        $body = $response->json();

        return new BankBoletoResult(
            bankBoletoId:     $body['nossonumero'] ?? '',
            barcode:          $body['linhaDigitavel'] ?? '',
            digitableLine:    $body['linhaDigitavel'] ?? '',
            pixQrCode:        $body['linkpix'] ?? null,
            pdfUrl:           $body['linkBoleto'] ?? null,
            ddaRegistered:    true,
            tokenFacilitador: $body['token_facilitador'] ?? null,
            requestPayload:   $payload,
            responsePayload:  is_array($body) ? $body : [],
        );
    }

    public function cancelBoleto(string $bankBoletoId, BoletoConfig $config): void
    {
        $creds      = $config->getCredentials();
        $credencial = $creds['api_key'];
        $chave      = $creds['chave'];

        $response = Http::withHeaders(['x-chave' => $chave])
            ->delete("{$config->bankPartner->base_url}/recebimentos/{$credencial}/transacoes/{$bankBoletoId}");

        if ($response->failed()) {
            throw new BankPartnerException(
                "PJBank: falha no cancelamento — HTTP {$response->status()}: " . $response->body()
            );
        }
    }

    public function getBoletoStatus(string $bankBoletoId, BoletoConfig $config): array
    {
        $creds      = $config->getCredentials();
        $credencial = $creds['api_key'];
        $chave      = $creds['chave'];

        $response = Http::withHeaders(['x-chave' => $chave])
            ->get("{$config->bankPartner->base_url}/recebimentos/{$credencial}/transacoes/{$bankBoletoId}");

        if ($response->failed()) {
            throw new BankPartnerException(
                "PJBank: falha na consulta — HTTP {$response->status()}: " . $response->body()
            );
        }

        $body = $response->json();
        $data = is_array($body) && isset($body[0]) ? $body[0] : $body;

        $valorPago     = $data['valor_pago'] ?? '';
        $dataPagamento = $data['data_pagamento'] ?? '';

        if (! empty($valorPago) && $valorPago !== '0' && ! empty($dataPagamento)) {
            $data['status']          = 'pago';
            $data['forma_pagamento'] = $data['forma_liquidacao'] ?? 'unknown';
        } elseif (in_array(strtolower($data['registro_sistema_bancario'] ?? ''), ['cancelado', 'cancelled'])) {
            $data['status'] = 'cancelado';
        } else {
            $data['status'] = 'pendente';
        }

        return $data;
    }
}
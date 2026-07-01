<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #1a1a1a; margin: 0; padding: 0; background: #f5f5f5; }
        .container { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0; }
        .header { background: #1a2a4a; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 18px; font-weight: 700; }
        .header p { margin: 4px 0 0; font-size: 13px; opacity: 0.8; }
        .body { padding: 32px; }
        .alert-box { background: #fff3cd; border: 1px solid #f0ad4e; border-radius: 6px; padding: 16px 20px; margin-bottom: 24px; }
        .alert-box p { margin: 0; font-size: 14px; color: #856404; }
        .info-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .info-table td { padding: 8px 12px; border-bottom: 1px solid #f0f0f0; }
        .info-table td:first-child { color: #555; font-weight: 600; width: 40%; }
        .progress-bar-bg { background: #e9ecef; border-radius: 4px; height: 12px; margin-top: 6px; }
        .progress-bar-fill { background: #f0ad4e; border-radius: 4px; height: 12px; }
        .footer { padding: 16px 32px; background: #f8f9fa; font-size: 12px; color: #888; border-top: 1px solid #e0e0e0; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Payproxy — Alerta de Limite Mensal</h1>
        <p>Plataforma de Boletos com Split de Pagamento</p>
    </div>
    <div class="body">
        <div class="alert-box">
            <p>
                ⚠️ A API key <strong>{{ $apiKey->name }}</strong> do tenant
                <strong>{{ $apiKey->tenant->name }}</strong> atingiu
                <strong>{{ $percentUsed }}%</strong> do limite mensal de emissões.
            </p>
        </div>

        <table class="info-table">
            <tr>
                <td>Tenant</td>
                <td>{{ $apiKey->tenant->name }}</td>
            </tr>
            <tr>
                <td>API Key</td>
                <td>{{ $apiKey->name }} ({{ $apiKey->key_prefix }}...)</td>
            </tr>
            <tr>
                <td>Emissões no mês</td>
                <td>{{ number_format($currentCount) }} de {{ number_format($monthlyLimit) }}</td>
            </tr>
            <tr>
                <td>Uso</td>
                <td>
                    <strong>{{ $percentUsed }}%</strong>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: {{ min($percentUsed, 100) }}%;"></div>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Mês de referência</td>
                <td>{{ now()->translatedFormat('F \d\e Y') }}</td>
            </tr>
        </table>

        <p style="margin-top: 24px; font-size: 13px; color: #555;">
            Acesse o backoffice para revisar a configuração desta API key ou aumentar o limite mensal caso necessário.
        </p>
    </div>
    <div class="footer">
        Este é um alerta automático do sistema Payproxy. Não responda a este e-mail.
    </div>
</div>
</body>
</html>

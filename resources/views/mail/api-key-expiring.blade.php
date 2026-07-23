<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Key expirando — Payproxy</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                <!-- Header -->
                <tr>
                    <td style="background:#2d5294;border-radius:12px 12px 0 0;padding:28px 36px;">
                        <span style="font-size:20px;font-weight:700;color:#ffffff;">Payproxy</span>
                        <span style="display:inline-block;background:rgba(255,255,255,0.15);border-radius:8px;padding:6px 14px;font-size:12px;color:#fff;font-weight:500;float:right;">
                            Aviso de Expiração
                        </span>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="background:#ffffff;padding:36px;border-radius:0 0 12px 12px;">
                        <h1 style="margin:0 0 8px;font-size:20px;font-weight:700;color:#1e293b;">
                            Olá, {{ $apiKey->tenant->name }}!
                        </h1>
                        <p style="margin:0 0 24px;font-size:15px;color:#64748b;line-height:1.6;">
                            Sua API Key <strong>{{ $apiKey->name }}</strong> (prefixo <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:13px;">{{ $apiKey->key_prefix }}••••</code>)
                            expira em <strong>{{ $daysLeft }} {{ $daysLeft === 1 ? 'dia' : 'dias' }}</strong>.
                        </p>

                        <div style="background:#fef9c3;border:1px solid #fde047;border-radius:10px;padding:20px 24px;margin-bottom:24px;">
                            <p style="margin:0;font-size:13px;color:#713f12;line-height:1.6;">
                                Após a expiração, requisições usando esta chave retornarão <strong>HTTP 401</strong> e as integrações associadas pararão de funcionar.
                            </p>
                        </div>

                        <p style="margin:0 0 20px;font-size:14px;color:#334155;line-height:1.6;">
                            Para evitar interrupções, acesse o backoffice e:
                        </p>

                        <ul style="margin:0 0 24px;padding-left:20px;font-size:14px;color:#475569;line-height:1.8;">
                            <li>Gere uma nova API Key e atualize suas integrações; ou</li>
                            <li>Edite a chave atual para prorrogar a data de expiração.</li>
                        </ul>

                        <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.6;">
                            Este aviso foi enviado para o e-mail cadastrado no tenant <strong>{{ $apiKey->tenant->name }}</strong>.<br>
                            © {{ date('Y') }} Payproxy
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

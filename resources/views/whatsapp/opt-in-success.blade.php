<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consentimento confirmado</title>
    <style>
        body { margin: 0; padding: 40px 16px; font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; }
        .card { background: #fff; border-radius: 16px; padding: 48px 40px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 2px 16px rgba(0,0,0,.08); }
        .icon { font-size: 56px; margin-bottom: 20px; }
        h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0 0 12px; }
        p { font-size: 15px; color: #64748b; line-height: 1.6; margin: 0 0 8px; }
        small { font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✅</div>
        <h1>Consentimento registrado!</h1>
        <p>Você autorizou o envio de notificações via WhatsApp por <strong>{{ $tenantName }}</strong>.</p>
        <p>A partir do próximo boleto, você receberá as notificações também pelo WhatsApp.</p>
        <br>
        <small>Para revogar esta autorização, entre em contato com {{ $tenantName }}.</small>
    </div>
</body>
</html>

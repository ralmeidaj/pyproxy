<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"/>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;font-size:14px;color:#1a1a1a;background:#f5f5f5;margin:0;padding:20px}
.card{max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.header{background:linear-gradient(135deg,#1a2a4a,#2d5294);padding:28px 32px;color:#fff}
.header h1{margin:0;font-size:20px;font-weight:700}
.header p{margin:4px 0 0;font-size:13px;opacity:.75}
.body{padding:28px 32px}
.body p{line-height:1.7;margin:0 0 16px;color:#444}
.btn{display:inline-block;background:#1a6fb5;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:600;margin:8px 4px 8px 0}
.btn-outline{background:#fff;color:#1a2a4a;border:2px solid #1a2a4a}
.notice{background:#f0f4f8;border-left:4px solid #1a6fb5;border-radius:0 6px 6px 0;padding:12px 16px;font-size:13px;color:#555;margin-top:20px}
.footer{padding:16px 32px;border-top:1px solid #eee;font-size:11px;color:#999;text-align:center}
</style>
</head>
<body>
<div class="card">
  <div class="header">
    <h1>Acesso ao Portal do Contribuinte</h1>
    <p>Payproxy — Plataforma de Arrecadação</p>
  </div>
  <div class="body">
    <p>Você solicitou acesso ao portal do contribuinte. Clique no botão abaixo para consultar seus débitos ou acessar seus dados pessoais.</p>

    <a href="{{ $debitosUrl }}" class="btn">Ver meus débitos</a>
    <a href="{{ $dadosUrl }}" class="btn btn-outline">Meus Dados (LGPD)</a>

    <div class="notice">
      <strong>Link válido até {{ $expiresAt }}</strong><br/>
      Este link é de uso pessoal. Não o compartilhe com terceiros.
      Após o prazo, solicite um novo acesso na página do portal.
    </div>
  </div>
  <div class="footer">
    Payproxy &mdash; Plataforma de Arrecadação Municipal &bull; Este é um e-mail automático, não responda.
  </div>
</div>
</body>
</html>

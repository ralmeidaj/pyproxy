<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"/>
<style>
body{font-family:'DejaVu Sans',sans-serif;font-size:10pt;color:#1a1a1a;margin:0;padding:0}
h1{font-size:14pt;color:#1a2a4a;margin:0 0 4px}
h2{font-size:11pt;color:#1a4a6b;margin:16px 0 6px;border-bottom:1px solid #d0dae8;padding-bottom:4px}
.header{background:#1a2a4a;color:#fff;padding:16px 20px;margin-bottom:20px}
.header h1{color:#fff;margin:0}
.header p{margin:2px 0 0;font-size:9pt;opacity:.75}
.meta{display:table;width:100%;background:#f0f4f8;border-radius:4px;padding:10px 14px;margin-bottom:16px;font-size:9pt}
.meta-item{display:table-cell;padding-right:24px}
.meta-label{color:#666;font-size:8pt}
dl{margin:0}
dt{color:#666;font-size:8.5pt;margin:8px 0 2px}
dd{margin:0;font-size:10pt;font-weight:600}
table{width:100%;border-collapse:collapse;font-size:9pt;margin-top:8px}
th{background:#1a2a4a;color:#fff;padding:6px 10px;text-align:left}
td{padding:5px 10px;border-bottom:1px solid #eee}
tr:nth-child(even) td{background:#f8f9fc}
.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:8pt;font-weight:600}
.badge-pending{background:#fef3c7;color:#92400e}
.badge-paid{background:#d1fae5;color:#065f46}
.badge-cancelled,.badge-expired{background:#f1f5f9;color:#64748b}
.legal{font-size:8pt;color:#888;border-top:1px solid #eee;margin-top:20px;padding-top:12px;line-height:1.5}
</style>
</head>
<body>

<div class="header">
  <h1>Relatório de Dados Pessoais</h1>
  <p>Payproxy — Plataforma de Arrecadação Municipal &bull; Art. 18 LGPD</p>
</div>

<div class="meta">
  <div class="meta-item"><div class="meta-label">Gerado em</div>{{ $date }}</div>
  <div class="meta-item"><div class="meta-label">Identificador (hash CPF)</div>{{ substr($cpfHash, 0, 16) }}…</div>
  <div class="meta-item"><div class="meta-label">Total de débitos</div>{{ $data['boleto_count'] }}</div>
</div>

<h2>Dados Cadastrais</h2>
<dl>
  <dt>Nome</dt><dd>{{ $data['payer_name'] ?? '—' }}</dd>
  <dt>E-mail</dt><dd>{{ $data['payer_email'] ?? '—' }}</dd>
  <dt>Telefone</dt><dd>{{ $data['payer_phone'] ?? '—' }}</dd>
  @if(!empty($data['payer_address']))
  <dt>Endereço</dt>
  <dd>
    {{ $data['payer_address']['logradouro'] ?? '' }}
    {{ $data['payer_address']['numero'] ?? '' }}
    @if(!empty($data['payer_address']['complemento'])), {{ $data['payer_address']['complemento'] }}@endif
    — {{ $data['payer_address']['bairro'] ?? '' }},
    {{ $data['payer_address']['cidade'] ?? '' }}/{{ $data['payer_address']['estado'] ?? '' }}
    — CEP {{ $data['payer_address']['cep'] ?? '' }}
  </dd>
  @endif
</dl>

<h2>Histórico de Débitos</h2>
<table>
  <thead>
    <tr>
      <th>Referência</th>
      <th>Município</th>
      <th>Valor</th>
      <th>Vencimento</th>
      <th>Status</th>
      <th>Emitido em</th>
    </tr>
  </thead>
  <tbody>
    @forelse($data['boletos'] as $b)
    <tr>
      <td>{{ $b['external_ref'] }}</td>
      <td>{{ $b['tenant_name'] ?? '—' }}</td>
      <td>R$ {{ number_format($b['amount_cents'] / 100, 2, ',', '.') }}</td>
      <td>{{ $b['due_date'] ? \Carbon\Carbon::parse($b['due_date'])->format('d/m/Y') : '—' }}</td>
      <td><span class="badge badge-{{ $b['status'] }}">{{ $b['status_label'] }}</span></td>
      <td>{{ $b['created_at'] ? \Carbon\Carbon::parse($b['created_at'])->format('d/m/Y') : '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="6" style="text-align:center;color:#888">Nenhum débito encontrado.</td></tr>
    @endforelse
  </tbody>
</table>

<div class="legal">
  <strong>Aviso Legal:</strong> Este relatório foi gerado em conformidade com o Art. 18 da Lei Geral de Proteção de Dados (LGPD — Lei 13.709/2018).
  Os dados fiscais (valor, data de vencimento, referência do débito) são retidos por <strong>mínimo de 5 anos</strong> conforme o Art. 195 do Código Tributário Nacional (CTN),
  mesmo após solicitação de anonimização de dados pessoais. A anonimização não implica exclusão de registros fiscais.
</div>

</body>
</html>

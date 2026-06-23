@extends('mail.boleto._base')

@php
    $amount  = 'R$ ' . number_format($boleto->amount_cents / 100, 2, ',', '.');
    $dueDate = $boleto->due_date->format('d/m/Y');
@endphp

@section('subject', 'Boleto cancelado')
@section('badge', 'Cancelado')

@section('content')
<h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#1e293b;">
    Boleto Cancelado
</h1>
<p style="margin:0 0 24px;font-size:15px;color:#64748b;line-height:1.6;">
    Olá, {{ $boleto->payer_name }}. O boleto abaixo foi cancelado e não pode mais ser utilizado para pagamento.
</p>

<!-- Info Card -->
<table width="100%" cellpadding="0" cellspacing="0"
    style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;margin-bottom:24px;">
    <tr>
        <td style="padding:20px 24px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width:50%;padding-right:12px;">
                                    <span style="font-size:11px;color:#94a3b8;display:block;">Valor</span>
                                    <span style="font-size:18px;font-weight:700;color:#9a3412;text-decoration:line-through;">{{ $amount }}</span>
                                </td>
                                <td style="width:50%;">
                                    <span style="font-size:11px;color:#94a3b8;display:block;">Vencimento original</span>
                                    <span style="font-size:13px;font-weight:600;color:#334155;">{{ $dueDate }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Aviso -->
<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:16px 20px;margin-bottom:24px;">
    <p style="margin:0;font-size:13px;color:#92400e;line-height:1.6;">
        ⚠️ <strong>Atenção:</strong> Caso este cancelamento tenha sido realizado por engano ou você precise de um novo boleto, entre em contato com a empresa responsável pela cobrança.
    </p>
</div>

<p style="margin:0;font-size:13px;color:#94a3b8;line-height:1.6;">
    Referência: <span style="font-family:monospace;color:#64748b;">{{ $boleto->external_ref }}</span>
</p>
@endsection

@extends('mail.boleto._base')

@php
    $amount  = 'R$ ' . number_format($boleto->amount_cents / 100, 2, ',', '.');
    $dueDate = $boleto->due_date->format('d/m/Y');
@endphp

@section('subject', 'Boleto vencido')
@section('badge', '⚠️ Vencido')

@section('content')
<h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#1e293b;">
    Boleto Vencido
</h1>
<p style="margin:0 0 24px;font-size:15px;color:#64748b;line-height:1.6;">
    Olá, {{ $boleto->payer_name }}. Identificamos que seu boleto com vencimento em <strong style="color:#dc2626;">{{ $dueDate }}</strong> ainda não foi pago.
</p>

<!-- Info Card -->
<table width="100%" cellpadding="0" cellspacing="0"
    style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;margin-bottom:24px;">
    <tr>
        <td style="padding:20px 24px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding-bottom:12px;border-bottom:1px solid #fecaca;">
                        <span style="font-size:12px;color:#dc2626;display:block;margin-bottom:2px;">Valor original</span>
                        <span style="font-size:24px;font-weight:700;color:#b91c1c;">{{ $amount }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:12px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width:50%;padding-right:12px;">
                                    <span style="font-size:11px;color:#94a3b8;display:block;">Vencimento</span>
                                    <span style="font-size:13px;font-weight:600;color:#dc2626;">{{ $dueDate }}</span>
                                </td>
                                <td style="width:50%;">
                                    <span style="font-size:11px;color:#94a3b8;display:block;">Referência</span>
                                    <span style="font-size:11px;font-family:monospace;color:#64748b;">{{ $boleto->external_ref }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Orientação -->
<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:16px 20px;margin-bottom:24px;">
    <p style="margin:0;font-size:13px;color:#92400e;line-height:1.6;">
        ⚠️ <strong>O que fazer agora?</strong> Entre em contato com a empresa responsável pela cobrança para solicitar um novo boleto atualizado ou regularizar sua situação.
    </p>
</div>

<p style="margin:0;font-size:13px;color:#94a3b8;line-height:1.6;">
    Este boleto foi marcado como vencido em nosso sistema. Boletos vencidos não podem mais ser pagos.
</p>
@endsection

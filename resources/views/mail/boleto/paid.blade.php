@extends('mail.boleto._base')

@php
    $amount  = 'R$ ' . number_format($boleto->amount_cents / 100, 2, ',', '.');
    $paidAt  = $boleto->paid_at ? $boleto->paid_at->format('d/m/Y \à\s H:i') : now()->format('d/m/Y \à\s H:i');
@endphp

@section('subject', 'Pagamento confirmado')
@section('badge', '✅ Pago')

@section('content')
<!-- Ícone de sucesso -->
<div style="text-align:center;margin-bottom:24px;">
    <div style="display:inline-block;background:#dcfce7;border-radius:50%;width:64px;height:64px;line-height:64px;font-size:32px;">
        ✅
    </div>
</div>

<h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#1e293b;text-align:center;">
    Pagamento Confirmado!
</h1>
<p style="margin:0 0 24px;font-size:15px;color:#64748b;line-height:1.6;text-align:center;">
    Olá, {{ $boleto->payer_name }}! Recebemos seu pagamento com sucesso.
</p>

<!-- Comprovante resumido -->
<table width="100%" cellpadding="0" cellspacing="0"
    style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;margin-bottom:24px;">
    <tr>
        <td style="padding:20px 24px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding-bottom:12px;border-bottom:1px solid #bbf7d0;text-align:center;">
                        <span style="font-size:12px;color:#16a34a;display:block;margin-bottom:4px;">Valor pago</span>
                        <span style="font-size:28px;font-weight:700;color:#15803d;">{{ $amount }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:16px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width:50%;padding-right:12px;">
                                    <span style="font-size:11px;color:#94a3b8;display:block;">Data do pagamento</span>
                                    <span style="font-size:13px;font-weight:600;color:#334155;">{{ $paidAt }}</span>
                                </td>
                                <td style="width:50%;">
                                    <span style="font-size:11px;color:#94a3b8;display:block;">Canal</span>
                                    <span style="font-size:13px;font-weight:600;color:#334155;">
                                        {{ $boleto->paid_channel ?? 'Banco' }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<p style="margin:0;font-size:13px;color:#94a3b8;line-height:1.6;text-align:center;">
    Guarde este e-mail como comprovante. Obrigado pela preferência!
</p>
@endsection

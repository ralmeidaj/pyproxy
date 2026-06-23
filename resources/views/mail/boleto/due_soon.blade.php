@extends('mail.boleto._base')

@php
    $amount  = 'R$ ' . number_format($boleto->amount_cents / 100, 2, ',', '.');
    $dueDate = $boleto->due_date->format('d/m/Y');
@endphp

@section('subject', 'Seu boleto vence em 2 dias')
@section('badge', '⏰ Vence em 2 dias')

@section('content')
<h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#1e293b;">
    Lembrete de Vencimento
</h1>
<p style="margin:0 0 24px;font-size:15px;color:#64748b;line-height:1.6;">
    Olá, {{ $boleto->payer_name }}! Seu boleto vence em <strong style="color:#d97706;">2 dias ({{ $dueDate }})</strong>. Não perca o prazo!
</p>

<!-- Info Card -->
<table width="100%" cellpadding="0" cellspacing="0"
    style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;margin-bottom:24px;">
    <tr>
        <td style="padding:20px 24px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding-bottom:12px;border-bottom:1px solid #fde68a;">
                        <span style="font-size:12px;color:#92400e;display:block;margin-bottom:2px;">Valor a pagar</span>
                        <span style="font-size:24px;font-weight:700;color:#d97706;">{{ $amount }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:12px;">
                        <span style="font-size:11px;color:#94a3b8;display:block;">Vencimento</span>
                        <span style="font-size:16px;font-weight:700;color:#d97706;">{{ $dueDate }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Linha digitável -->
@if($boleto->digitable_line)
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:16px 20px;margin-bottom:24px;">
    <p style="margin:0 0 6px;font-size:11px;color:#3b82f6;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">
        Linha Digitável
    </p>
    <p style="margin:0;font-size:13px;font-family:monospace;color:#1e40af;word-break:break-all;line-height:1.5;">
        {{ $boleto->digitable_line }}
    </p>
</div>
@endif

<!-- CTA Button -->
@if($boleto->pdf_url)
<table cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
    <tr>
        <td style="background:#d97706;border-radius:8px;">
            <a href="{{ $boleto->pdf_url }}" target="_blank"
                style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">
                📄 Baixar PDF do Boleto
            </a>
        </td>
    </tr>
</table>
@endif

<p style="margin:0;font-size:13px;color:#94a3b8;line-height:1.6;">
    Após o vencimento, o boleto poderá ser cancelado automaticamente. Pague em bancos, lotéricas ou pelo internet banking.
</p>
@endsection

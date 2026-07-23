@extends('mail.boleto._base')

@php
    $amount  = 'R$ ' . number_format($boleto->amount_cents / 100, 2, ',', '.');
    $dueDate = $boleto->due_date->format('d/m/Y');
@endphp

@section('subject', 'Seu boleto foi emitido')
@section('badge', 'Boleto Emitido')

@section('content')
<h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#1e293b;">
    Olá, {{ $boleto->payer_name }}!
</h1>
<p style="margin:0 0 24px;font-size:15px;color:#64748b;line-height:1.6;">
    Seu boleto foi gerado com sucesso. Confira os detalhes abaixo:
</p>

<!-- Info Card -->
<table width="100%" cellpadding="0" cellspacing="0"
    style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:24px;">
    <tr>
        <td style="padding:20px 24px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding-bottom:12px;border-bottom:1px solid #e2e8f0;">
                        <span style="font-size:12px;color:#94a3b8;display:block;margin-bottom:2px;">Valor</span>
                        <span style="font-size:24px;font-weight:700;color:#2d5294;">{{ $amount }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:12px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width:50%;padding-right:12px;">
                                    <span style="font-size:11px;color:#94a3b8;display:block;">Vencimento</span>
                                    <span style="font-size:13px;font-weight:600;color:#334155;">{{ $dueDate }}</span>
                                </td>
                                <td style="width:50%;">
                                    <span style="font-size:11px;color:#94a3b8;display:block;">Pagador</span>
                                    <span style="font-size:13px;font-weight:600;color:#334155;">{{ $boleto->payer_name }}</span>
                                </td>
                            </tr>
                        </table>
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

<!-- CTA Button — link AR Digital se disponível, senão link direto ao PDF -->
@php
    $ctaUrl   = $arNotification
        ? route('ar.boleto.show', ['token' => $arNotification->token])
        : $boleto->pdf_url;
    $ctaLabel = $arNotification ? 'Acessar Boleto' : '📄 Baixar PDF do Boleto';
@endphp

@if($ctaUrl)
<table cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
    <tr>
        <td style="background:#2d5294;border-radius:8px;">
            <a href="{{ $ctaUrl }}" target="_blank"
                style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">
                {{ $ctaLabel }}
            </a>
        </td>
    </tr>
</table>
@endif

<p style="margin:0;font-size:13px;color:#94a3b8;line-height:1.6;">
    Você pode pagar via internet banking, aplicativo do seu banco ou em qualquer agência bancária, lotérica ou correspondente bancário.
</p>

{{-- Opt-in WhatsApp --}}
@if(!empty($whatsappOptInUrl))
<div style="margin-top:24px;padding:16px 20px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;">
    <p style="margin:0 0 10px;font-size:13px;color:#166534;font-weight:600;">
        Receba também pelo WhatsApp!
    </p>
    <p style="margin:0 0 14px;font-size:13px;color:#166534;line-height:1.5;">
        Autorize o envio de notificações de boletos via WhatsApp e fique sempre informado.
    </p>
    <a href="{{ $whatsappOptInUrl }}" target="_blank"
        style="display:inline-block;background:#16a34a;color:#ffffff;padding:10px 20px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">
        Autorizar WhatsApp
    </a>
</div>
@endif

{{-- Pixel AR Digital (rastreamento de abertura) — invisível, deve ficar ao final do conteúdo --}}
@if($arNotification && $pixelTracking)
<img src="{{ route('ar.pixel', ['token' => $arNotification->token]) }}"
     width="1" height="1" alt=""
     style="display:block;width:1px;height:1px;border:0;opacity:0;">
@endif
@endsection

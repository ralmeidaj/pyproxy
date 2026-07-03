@php
    $entityName  = $boleto->tenant->email_entity_name ?? 'Payproxy';
    $entitySub   = $boleto->tenant->email_entity_name ? '' : 'Plataforma de Boletos Bancários';
    $logoUrl     = $boleto->tenant->email_logo_url ?? null;
    $customText  = $boleto->tenant->email_custom_text ?? null;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('subject', 'Notificação ' . $entityName)</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                <!-- Header -->
                <tr>
                    <td style="background:#2d5294;border-radius:12px 12px 0 0;padding:28px 36px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    @if($logoUrl)
                                        <img src="{{ $logoUrl }}" alt="{{ $entityName }}"
                                            style="height:48px;max-width:200px;object-fit:contain;display:block;">
                                    @else
                                        <span style="font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.3px;">{{ $entityName }}</span>
                                        @if($entitySub)
                                            <br>
                                            <span style="font-size:11px;color:rgba(255,255,255,0.7);margin-top:2px;display:block;">{{ $entitySub }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td align="right">
                                    <span style="display:inline-block;background:rgba(255,255,255,0.15);border-radius:8px;padding:6px 14px;font-size:12px;color:#fff;font-weight:500;">
                                        @yield('badge')
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="background:#ffffff;padding:36px;">
                        @yield('content')

                        @if($customText)
                            <div style="margin-top:28px;padding-top:20px;border-top:1px solid #e2e8f0;">
                                <p style="margin:0;font-size:13px;color:#475569;line-height:1.7;">{{ $customText }}</p>
                            </div>
                        @endif
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background:#f0f4f8;border-radius:0 0 12px 12px;padding:24px 36px;border-top:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.6;">
                            Este é um e-mail automático. Por favor, não responda diretamente.<br>
                            Você está recebendo este e-mail porque realizou uma transação através da plataforma {{ $entityName }}.
                        </p>
                        <p style="margin:12px 0 0;font-size:11px;color:#94a3b8;">
                            © {{ date('Y') }} {{ $entityName }}
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>

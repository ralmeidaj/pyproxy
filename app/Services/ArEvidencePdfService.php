<?php

namespace App\Services;

use App\Models\ArDigitalNotification;
use App\Models\Boleto;
use App\Models\Tenant;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArEvidencePdfService
{
    private const EVENTOS_LABEL = [
        'envio'            => 'Envio da Notificação',
        'entrega_provedor' => 'Entrega pelo Provedor (SMTP/Meta)',
        'abertura'         => 'Abertura (Pixel de Rastreamento)',
        'leitura_pixel'    => 'Abertura (Pixel de Rastreamento)',
        'leitura_email'    => 'Abertura do E-mail',
        'acesso_link'      => 'Acesso ao Link do Boleto',
        'confirmacao_cpf'  => 'Confirmação de Recebimento (CPF)',
        'confirmado'       => 'Confirmação de Recebimento (CPF)',
        'bounce'           => 'Falha de Entrega (Bounce)',
        'envio_whatsapp'   => 'Envio via WhatsApp',
    ];

    private const STATUS_LABELS = [
        'enviado'    => 'Enviado',
        'entregue'   => 'Entregue',
        'lido'       => 'Lido',
        'confirmado' => 'Confirmado',
        'bounce'     => 'Bounce (falha de entrega)',
    ];

    public function gerar(ArDigitalNotification $notification): string
    {
        $notification->loadMissing([
            'boleto.tenant',
            'events.timestamp',
        ]);

        $boleto    = $notification->boleto;
        $tenant    = $boleto->tenant;
        $verifyUrl = route('ar.boleto.show', ['token' => $notification->token]);
        $qrDataUri = $this->gerarQrCode($verifyUrl);

        $html = $this->buildHtml($notification, $boleto, $tenant, $qrDataUri, $verifyUrl);

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi'             => 150,
                'defaultFont'     => 'DejaVu Sans',
                'isPhpEnabled'    => false,
                'isRemoteEnabled' => false,
            ]);

        $pdfContent = $pdf->output();

        $path = sprintf('ar-digital/%s/laudo_%s.pdf', $notification->id, now()->format('Ymd_His'));

        Storage::disk('s3')->put($path, $pdfContent);

        Log::info('ArEvidencePdfService: laudo gerado', [
            'notification_id' => $notification->id,
            'path'            => $path,
            'eventos'         => $notification->events->count(),
        ]);

        return $path;
    }

    private function gerarQrCode(string $url): string
    {
        try {
            $renderer = new ImageRenderer(
                new RendererStyle(150),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $svg = $writer->writeString($url);
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Throwable $e) {
            Log::warning('ArEvidencePdfService: falha ao gerar QR code', ['error' => $e->getMessage()]);
            return '';
        }
    }

    private function buildHtml(
        ArDigitalNotification $notification,
        Boleto $boleto,
        Tenant $tenant,
        string $qrDataUri,
        string $verifyUrl,
    ): string {
        $geradoEm    = now()->format('d/m/Y \à\s H:i:s');
        $laudoRef    = 'ARD-' . str_pad($notification->id, 6, '0', STR_PAD_LEFT);
        $statusLabel = self::STATUS_LABELS[$notification->status] ?? $notification->status;
        $isStub      = ! $this->possuiCarimboReal($notification);

        $valorFormatted = 'R$ ' . number_format($boleto->amount_cents / 100, 2, ',', '.');
        $vencimento     = $boleto->due_date ? $boleto->due_date->format('d/m/Y') : '—';

        $emailMasked = $this->mascararEmail($notification->destinatario_email ?? '');
        $telefone    = $this->mascararTelefone($notification->destinatario_whatsapp ?? '');
        $cpfHash     = $notification->cpf_hash
            ? substr($notification->cpf_hash, 0, 32) . '...'
            : 'Não coletado';

        $eventoRows = $this->buildEventRows($notification);

        $stubAviso = $isStub
            ? '<p style="background-color:#fff3cd;border:1px solid #ffc107;color:#856404;font-size:7pt;font-weight:bold;text-align:center;margin:6px 0;padding:4px 8px;">AVISO: CARIMBOS RFC 3161 EM MODO DESENVOLVIMENTO — SEM VALIDADE JURÍDICA</p>'
            : '';

        $qrImg = $qrDataUri
            ? "<img src=\"{$qrDataUri}\" width=\"110\" height=\"110\" />"
            : '<p style="font-size:7pt;color:#999;">[QR indisponível]</p>';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8.5pt; color: #1a1a1a; margin: 0; padding: 0; }
  .hdr { background-color: #1a2a4a; color: #ffffff; padding: 14px 22px 10px 22px; }
  .hdr-title { font-size: 14pt; font-weight: bold; margin: 0; }
  .hdr-sub { font-size: 8pt; color: #a0b8d4; margin: 2px 0 0 0; }
  .hdr-ref { font-size: 7.5pt; color: #7098b8; margin: 4px 0 0 0; }
  .bluebar { background-color: #1a6fb5; height: 3px; }
  .wrap { padding: 0 22px 10px 22px; }
  .sec { margin-top: 11px; }
  .sec-title { font-size: 7.5pt; font-weight: bold; color: #1a6fb5; text-transform: uppercase;
               letter-spacing: 0.3px; padding-bottom: 3px; border-bottom: 1px solid #c8d8ea; margin-bottom: 7px; }
  .g2 { width: 100%; border-collapse: collapse; }
  .g2 td { vertical-align: top; padding: 2px 0; }
  .lbl { font-size: 7pt; color: #666; }
  .val { font-size: 8pt; color: #1a2a4a; font-weight: bold; margin-top: 1px; }
  .mono { font-size: 7pt; color: #1a3a5a; font-family: DejaVu Sans Mono, Courier New, monospace; word-break: break-all; margin-top: 1px; }
  table.ev { width: 100%; border-collapse: collapse; font-size: 7.5pt; }
  table.ev th { background-color: #1a2a4a; color: #ffffff; padding: 5px 6px; text-align: left; font-weight: bold; font-size: 7pt; }
  table.ev td { padding: 5px 6px; border-bottom: 1px solid #e0e8f0; vertical-align: middle; }
  table.ev tr.ev-alt td { background-color: #f5f8fc; }
  .ev-ok   { color: #1a7a1a; font-weight: bold; font-size: 7pt; }
  .ev-stub { color: #c47a00; font-size: 7pt; }
  .ev-no   { color: #cc3333; font-size: 7pt; }
  .footer { margin-top: 12px; background-color: #f0f4f8; padding: 8px 22px; border-top: 1px solid #c8d8ea; font-size: 6.5pt; color: #555; }
  .qr-wrap { text-align: center; padding: 4px 0; }
  .field-gap { height: 5px; }
</style>
</head>
<body>

<div class="hdr">
  <div class="hdr-title">Laudo de AR Digital</div>
  <div class="hdr-sub">Aviso de Recebimento Digital — Payproxy</div>
  <div class="hdr-ref">Referência: {$laudoRef} &nbsp;|&nbsp; Gerado em: {$geradoEm}</div>
</div>
<div class="bluebar"></div>

<div class="wrap">

{$stubAviso}

<!-- Seção 1: Identificação -->
<div class="sec">
  <div class="sec-title">1. Identificação do Laudo</div>
  <table class="g2"><tbody>
    <tr>
      <td width="50%" style="padding-right:12px;">
        <div class="lbl">Referência do Laudo</div>
        <div class="val">{$laudoRef}</div>
        <tr class="field-gap"></tr>
        <div class="lbl">Status da Notificação</div>
        <div class="val">{$statusLabel}</div>
      </td>
      <td width="50%">
        <div class="lbl">Token de Verificação (UUID)</div>
        <div class="mono">{$notification->token}</div>
        <tr class="field-gap"></tr>
        <div class="lbl">Hash SHA-256 do Documento do Boleto</div>
        <div class="mono">{$notification->hash_documento}</div>
      </td>
    </tr>
  </tbody></table>
</div>

<!-- Seção 2: Dados do Boleto -->
<div class="sec">
  <div class="sec-title">2. Dados do Boleto</div>
  <table class="g2"><tbody>
    <tr>
      <td width="34%" style="padding-right:10px;">
        <div class="lbl">Referência Externa</div>
        <div class="mono">{$boleto->external_ref}</div>
      </td>
      <td width="33%" style="padding-right:10px;">
        <div class="lbl">Valor</div>
        <div class="val">{$valorFormatted}</div>
      </td>
      <td width="33%">
        <div class="lbl">Vencimento</div>
        <div class="val">{$vencimento}</div>
      </td>
    </tr>
    <tr><td colspan="3" style="height:5px;"></td></tr>
    <tr>
      <td colspan="2" style="padding-right:10px;">
        <div class="lbl">Emitente (Tenant)</div>
        <div class="val">{$tenant->name}</div>
      </td>
      <td>
        <div class="lbl">ID no Banco</div>
        <div class="mono">{$boleto->bank_boleto_id}</div>
      </td>
    </tr>
  </tbody></table>
</div>

<!-- Seção 3: Destinatário (LGPD) -->
<div class="sec">
  <div class="sec-title">3. Dados do Destinatário (LGPD — dados parcialmente mascarados)</div>
  <table class="g2"><tbody>
    <tr>
      <td width="50%" style="padding-right:12px;">
        <div class="lbl">Nome do Pagador</div>
        <div class="val">{$boleto->payer_name}</div>
        <div style="height:5px;"></div>
        <div class="lbl">E-mail (mascarado)</div>
        <div class="mono">{$emailMasked}</div>
      </td>
      <td width="50%">
        <div class="lbl">Telefone (mascarado)</div>
        <div class="mono">{$telefone}</div>
        <div style="height:5px;"></div>
        <div class="lbl">Documento (CPF/CNPJ) — Hash SHA-256 irreversível</div>
        <div class="mono">{$cpfHash}</div>
      </td>
    </tr>
  </tbody></table>
</div>

<!-- Seção 4: Cadeia de Evidências -->
<div class="sec">
  <div class="sec-title">4. Cadeia de Evidências com Carimbos RFC 3161</div>
  <table class="ev">
    <thead>
      <tr>
        <th width="27%">Evento</th>
        <th width="11%">Canal</th>
        <th width="17%">Data/Hora (UTC)</th>
        <th width="13%">IP de Origem</th>
        <th width="32%">Carimbo RFC 3161</th>
      </tr>
    </thead>
    <tbody>
      {$eventoRows}
    </tbody>
  </table>
</div>

<!-- Seção 5: Verificação -->
<div class="sec">
  <div class="sec-title">5. Verificação Online</div>
  <table class="g2"><tbody>
    <tr>
      <td width="65%" style="vertical-align:middle; padding-right:12px;">
        <div class="lbl">Link de verificação deste laudo</div>
        <div class="mono" style="font-size:6.5pt;">{$verifyUrl}</div>
        <div style="height:5px;"></div>
        <div class="lbl">Acesse o link ou escaneie o QR code ao lado para verificar a autenticidade
        deste laudo e consultar os eventos registrados em tempo real na plataforma Payproxy.</div>
      </td>
      <td width="35%">
        <div class="qr-wrap">{$qrImg}</div>
      </td>
    </tr>
  </tbody></table>
</div>

</div><!-- /wrap -->

<div class="footer">
  <strong>Aviso LGPD (Lei 13.709/2018):</strong> Os dados pessoais contidos neste laudo foram coletados
  com a finalidade exclusiva de comprovar a entrega e recebimento do boleto bancário.
  O CPF/CNPJ é armazenado somente como hash SHA-256 irreversível, sem possibilidade de recuperação do dado original.
  Os dados podem ser suprimidos mediante solicitação formal ao controlador dos dados.
  <br>
  <strong>Validade jurídica:</strong> Este laudo utiliza carimbos de tempo RFC 3161 emitidos por Autoridade de Carimbo
  de Tempo (ACT) credenciada pelo Instituto Nacional de Tecnologia da Informação (ITI/ICP-Brasil),
  conferindo presunção de autoria e integridade conforme MP 2.200-2/2001 e Lei 14.063/2020.
</div>

</body>
</html>
HTML;
    }

    private function buildEventRows(ArDigitalNotification $notification): string
    {
        $rows  = '';
        $i     = 0;

        foreach ($notification->events->sortBy('ocorrido_em') as $event) {
            $label    = self::EVENTOS_LABEL[$event->tipo] ?? $event->tipo;
            $canal    = ucfirst($event->canal ?? '—');
            $ocorrido = $event->ocorrido_em
                ? $event->ocorrido_em->utc()->format('d/m/Y H:i:s')
                : '—';
            $ip       = htmlspecialchars($event->ip ?? '—');
            $class    = ($i % 2 === 1) ? ' class="ev-alt"' : '';

            $ts = $event->timestamp;
            if ($ts) {
                if ($ts->act_provider === 'stub-dev') {
                    $carimbo = '<span class="ev-stub">DEV — sem validade jur&iacute;dica</span>'
                        . '<br><span style="font-size:6pt;color:#999;font-family:monospace;">'
                        . htmlspecialchars(substr($ts->hash_input, 0, 28)) . '...</span>';
                } else {
                    $carimbo = '<span class="ev-ok">&#10003; ' . strtoupper(htmlspecialchars($ts->act_provider)) . '</span>'
                        . '<br><span style="font-size:6pt;color:#555;font-family:monospace;">'
                        . htmlspecialchars(substr($ts->hash_input, 0, 28)) . '...</span>'
                        . '<br><span style="font-size:6pt;color:#999;">'
                        . ($ts->verificado_em ? $ts->verificado_em->format('d/m/Y H:i:s') : '') . '</span>';
                }
            } else {
                $carimbo = '<span class="ev-no">Pendente</span>';
            }

            $rows .= "<tr{$class}>"
                . "<td>" . htmlspecialchars($label) . "</td>"
                . "<td>" . htmlspecialchars($canal) . "</td>"
                . "<td style=\"font-size:7pt;\">{$ocorrido}</td>"
                . "<td style=\"font-size:6.5pt;font-family:monospace;\">{$ip}</td>"
                . "<td>{$carimbo}</td>"
                . "</tr>\n";

            $i++;
        }

        return $rows ?: '<tr><td colspan="5" style="text-align:center;color:#999;font-size:7pt;">Nenhum evento registrado.</td></tr>';
    }

    private function possuiCarimboReal(ArDigitalNotification $notification): bool
    {
        return $notification->events->contains(
            fn ($ev) => $ev->timestamp && $ev->timestamp->act_provider !== 'stub-dev'
        );
    }

    private function mascararEmail(string $email): string
    {
        if (! $email) return '—';
        $parts = explode('@', $email);
        if (count($parts) !== 2) return '—';
        $user = $parts[0];
        $masked = strlen($user) > 2
            ? substr($user, 0, 2) . str_repeat('*', strlen($user) - 2)
            : $user[0] . '***';
        return $masked . '@' . $parts[1];
    }

    private function mascararTelefone(string $tel): string
    {
        if (! $tel) return '—';
        $digits = preg_replace('/\D/', '', $tel);
        if (strlen($digits) < 8) return '—';
        return substr($digits, 0, 4) . '****' . substr($digits, -4);
    }
}

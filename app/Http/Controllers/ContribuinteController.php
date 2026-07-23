<?php

namespace App\Http\Controllers;

use App\Helpers\MaskHelper;
use App\Mail\ContribuinteAccessLinkMail;
use App\Models\AnonymizationRequest;
use App\Models\ContribuinteAccessToken;
use App\Services\ContribuinteService;
use App\Services\MeusDadosPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContribuinteController extends Controller
{
    public function __construct(
        private readonly ContribuinteService  $service,
        private readonly MeusDadosPdfService  $pdfService,
    ) {}

    public function show(): Response
    {
        return Inertia::render('Contribuinte/Index');
    }

    public function verificar(Request $request): RedirectResponse
    {
        $cpf    = $request->input('cpf', '');
        $digits = preg_replace('/\D/', '', $cpf);

        if (strlen($digits) !== 11 && strlen($digits) !== 14) {
            return back()->withErrors(['cpf' => 'CPF ou CNPJ inválido.']);
        }

        $email = $this->service->findEmailByCpf($cpf);

        if (! $email) {
            return back()->withErrors(['cpf' => 'Nenhum débito encontrado para este CPF/CNPJ na plataforma.']);
        }

        $token = ContribuinteAccessToken::generate($cpf);
        Mail::to($email)->send(new ContribuinteAccessLinkMail($token));

        return back()->with('success', 'Link de acesso enviado para o e-mail cadastrado. Verifique sua caixa de entrada.');
    }

    public function debitos(string $token): Response|RedirectResponse
    {
        $accessToken = ContribuinteAccessToken::findValid($token);

        if (! $accessToken) {
            return redirect()->route('contribuinte.show')
                ->withErrors(['token' => 'Link expirado ou inválido. Solicite um novo acesso.']);
        }

        $accessToken->markAsUsed();

        $boletos = $this->service->getBoletos($accessToken);
        $tenants = $boletos
            ->groupBy(fn ($b) => $b->tenant?->name ?? 'Município não identificado')
            ->map(fn ($items, $name) => [
                'name'    => $name,
                'boletos' => $items->map(fn ($b) => [
                    'id'             => $b->id,
                    'external_ref'   => $b->external_ref,
                    'amount_cents'   => $b->amount_cents,
                    'due_date'       => $b->due_date?->format('Y-m-d'),
                    'status'         => $b->status instanceof \BackedEnum ? $b->status->value : $b->status,
                    'status_label'   => $b->status instanceof \App\Enums\BoletoStatus ? $b->status->label() : (string) $b->status,
                    'digitable_line' => $b->digitable_line,
                    'pdf_url'        => $b->pdf_url,
                    'payer_name'     => $b->payer_name,
                ])->values(),
            ])->values();

        return Inertia::render('Contribuinte/Debitos', [
            'token'   => $token,
            'tenants' => $tenants,
        ]);
    }

    public function meusDados(string $token): Response|RedirectResponse
    {
        $accessToken = ContribuinteAccessToken::findValid($token);

        if (! $accessToken) {
            return redirect()->route('contribuinte.show')
                ->withErrors(['token' => 'Link expirado ou inválido. Solicite um novo acesso.']);
        }

        $data = $this->service->getPersonalData($accessToken);

        $alreadyRequested = AnonymizationRequest::where('cpf_hash', $accessToken->cpf_hash)
            ->where('status', 'pending')
            ->exists();

        return Inertia::render('Contribuinte/MeusDados', [
            'token'            => $token,
            'dados'            => $data,
            'alreadyRequested' => $alreadyRequested,
        ]);
    }

    public function exportar(string $token): \Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        $accessToken = ContribuinteAccessToken::findValid($token);

        if (! $accessToken) {
            return redirect()->route('contribuinte.show')
                ->withErrors(['token' => 'Link expirado.']);
        }

        $data = $this->service->getPersonalData($accessToken);
        $pdf  = $this->pdfService->generate($data, $accessToken->cpf_hash);

        return $pdf->download('meus-dados-payproxy-' . now()->format('Ymd') . '.pdf');
    }

    public function solicitarExclusao(string $token): RedirectResponse
    {
        $accessToken = ContribuinteAccessToken::findValid($token);

        if (! $accessToken) {
            return redirect()->route('contribuinte.show')
                ->withErrors(['token' => 'Link expirado.']);
        }

        $already = AnonymizationRequest::where('cpf_hash', $accessToken->cpf_hash)
            ->where('status', 'pending')
            ->exists();

        if ($already) {
            return back()->with('info', 'Você já possui uma solicitação de exclusão em análise.');
        }

        $boletoIds = $this->service->getBoletoIdsByToken($accessToken);
        $data      = $this->service->getPersonalData($accessToken);

        AnonymizationRequest::create([
            'cpf_hash'          => $accessToken->cpf_hash,
            'boleto_ids'        => $boletoIds,
            'payer_email_masked' => MaskHelper::email($data['payer_email'] ?? ''),
            'boleto_count'      => count($boletoIds),
            'status'            => 'pending',
        ]);

        return back()->with('success', 'Solicitação registrada com sucesso. Analisaremos em até 15 dias úteis conforme previsto na LGPD.');
    }
}

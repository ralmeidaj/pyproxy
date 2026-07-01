<?php

namespace App\Http\Controllers\Portal;

use App\DTOs\IssueBoletoData;
use App\Enums\BoletoStatus;
use App\Enums\NotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\Boleto;
use App\Services\BoletoService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BoletoController extends Controller
{
    public function __construct(
        private readonly BoletoService      $boletoService,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): Response
    {
        $tenant = Auth::guard('portal')->user()->tenant;

        $query = $tenant->boletos()->with('boletoConfig:id,name')->orderByDesc('created_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('external_ref', 'ilike', "%{$search}%")
                  ->orWhere('payer_name', 'ilike', "%{$search}%")
                  ->orWhere('payer_document', 'ilike', "%{$search}%");
            });
        }

        $boletos = $query->paginate(20)->through(fn ($b) => array_merge(
            $b->only('id', 'external_ref', 'status', 'amount_cents', 'due_date', 'payer_name', 'boleto_config_id', 'created_at'),
            [
                'status_label'  => $b->status->label(),
                'boleto_config' => $b->boletoConfig?->only('id', 'name'),
            ]
        ));

        return Inertia::render('Portal/Boletos/Index', [
            'boletos'  => $boletos,
            'filters'  => $request->only('status', 'search'),
            'statuses' => collect(BoletoStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'canWrite' => Auth::guard('portal')->user()->canWrite(),
        ]);
    }

    public function create(): Response
    {
        $tenant  = Auth::guard('portal')->user()->tenant;
        $configs = $tenant->boletoConfigs()
            ->where('status', 'active')
            ->get(['id', 'name', 'is_default']);

        abort_if($configs->isEmpty(), 422, 'Nenhuma configuração de boleto ativa para este tenant.');

        return Inertia::render('Portal/Boletos/Create', [
            'configs'        => $configs,
            'defaultConfigId' => $configs->firstWhere('is_default', true)?->id ?? $configs->first()->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user   = Auth::guard('portal')->user();
        $tenant = $user->tenant;

        abort_if(! $user->canWrite(), 403, 'Sem permissão para emitir boletos.');

        $validated = $request->validate([
            'config_id'      => ['required', 'integer', 'exists:boleto_configs,id'],
            'external_ref'   => ['required', 'string', 'max:100'],
            'payer_name'     => ['required', 'string', 'max:150'],
            'payer_document' => ['required', 'string', 'max:18'],
            'payer_email'    => ['nullable', 'email', 'max:150'],
            'payer_phone'    => ['nullable', 'string', 'max:20'],
            'amount'         => ['required', 'numeric', 'min:1'],
            'due_date'       => ['required', 'date', 'after_or_equal:today'],
            'metadata'       => ['nullable', 'array'],
        ], [
            'config_id.required'      => 'Selecione a configuração de boleto.',
            'external_ref.required'   => 'A referência externa é obrigatória.',
            'payer_name.required'     => 'O nome do pagador é obrigatório.',
            'payer_document.required' => 'O CPF/CNPJ do pagador é obrigatório.',
            'amount.required'         => 'O valor é obrigatório.',
            'amount.min'              => 'O valor mínimo é R$ 1,00.',
            'due_date.required'       => 'A data de vencimento é obrigatória.',
            'due_date.after_or_equal' => 'O vencimento não pode ser no passado.',
        ]);

        // Confirmar que a config pertence ao tenant
        abort_if(
            ! $tenant->boletoConfigs()->where('id', $validated['config_id'])->where('status', 'active')->exists(),
            403,
            'Configuração de boleto inválida.'
        );

        $amountCents = (int) round($validated['amount'] * 100);

        $data = new IssueBoletoData(
            externalRef:   $validated['external_ref'],
            amountCents:   $amountCents,
            dueDate:       $validated['due_date'],
            payerName:     $validated['payer_name'],
            payerDocument: $validated['payer_document'],
            payerEmail:    $validated['payer_email'] ?? null,
            payerPhone:    $validated['payer_phone'] ?? null,
            metadata:      array_merge($validated['metadata'] ?? [], ['issued_via' => 'portal']),
        );

        $boleto = $this->boletoService->issue($tenant, $data, $validated['config_id']);

        return redirect()->route('portal.boletos.show', $boleto->id)
            ->with('success', 'Boleto emitido com sucesso!');
    }

    public function show(Boleto $boleto): Response
    {
        $user   = Auth::guard('portal')->user();
        $tenant = $user->tenant;

        abort_if($boleto->tenant_id !== $tenant->id, 404);

        $boleto->load('splits', 'boletoConfig:id,name');

        return Inertia::render('Portal/Boletos/Show', [
            'boleto' => array_merge($boleto->toArray(), [
                'status_label' => $boleto->status->label(),
                'can_cancel'   => $boleto->status->canCancel() && $user->canWrite(),
                'can_resend'   => $boleto->status === BoletoStatus::Pending
                                  && $boleto->payer_email !== null
                                  && $user->canWrite(),
            ]),
        ]);
    }

    public function resend(Boleto $boleto): RedirectResponse
    {
        $user   = Auth::guard('portal')->user();
        $tenant = $user->tenant;

        abort_if($boleto->tenant_id !== $tenant->id, 404);
        abort_if(! $user->canWrite(), 403, 'Sem permissão para reenviar notificações.');
        abort_if($boleto->status !== BoletoStatus::Pending, 422, 'Só é possível reenviar notificações de boletos pendentes.');
        abort_if(! $boleto->payer_email, 422, 'Este boleto não possui e-mail do pagador cadastrado.');

        $this->notifications->notify($boleto, NotificationEvent::Issued);

        return back()->with('success', 'Notificação reenviada com sucesso.');
    }

    public function cancel(Boleto $boleto): RedirectResponse
    {
        $user   = Auth::guard('portal')->user();
        $tenant = $user->tenant;

        abort_if($boleto->tenant_id !== $tenant->id, 404);
        abort_if(! $user->canWrite(), 403, 'Sem permissão para cancelar boletos.');

        $this->boletoService->cancel($boleto, "Cancelado via portal pelo usuário {$user->email}");

        return redirect()->route('portal.boletos.show', $boleto->id)
            ->with('success', 'Boleto cancelado com sucesso.');
    }
}

<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AnonymizationRequest;
use App\Models\Boleto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnonymizationRequestController extends Controller
{
    public function index(): Response
    {
        $requests = AnonymizationRequest::orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(30);

        return Inertia::render('Backoffice/AnonymizationRequests/Index', [
            'requests' => $requests,
        ]);
    }

    public function process(Request $request, AnonymizationRequest $anonymizationRequest): RedirectResponse
    {
        abort_if(! $anonymizationRequest->isPending(), 422, 'Esta solicitação já foi processada.');

        $action = $request->input('action');
        $actor  = Auth::guard('backoffice')->user();

        if ($action === 'approve') {
            Boleto::withTrashed()
                ->whereIn('id', $anonymizationRequest->boleto_ids)
                ->update([
                    'payer_name'     => null,
                    'payer_document' => null,
                    'payer_email'    => null,
                    'payer_phone'    => null,
                    'payer_address'  => null,
                ]);

            $anonymizationRequest->update([
                'status'             => 'done',
                'processed_at'       => now(),
                'processed_by_label' => $actor?->email ?? 'system',
            ]);

            return back()->with('success', "Dados anonimizados com sucesso ({$anonymizationRequest->boleto_count} boleto(s)).");
        }

        $anonymizationRequest->update([
            'status'             => 'rejected',
            'processed_at'       => now(),
            'processed_by_label' => $actor?->email ?? 'system',
            'notes'              => $request->input('notes'),
        ]);

        return back()->with('success', 'Solicitação rejeitada.');
    }
}

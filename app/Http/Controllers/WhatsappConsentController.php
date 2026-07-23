<?php

namespace App\Http\Controllers;

use App\Models\Boleto;
use App\Models\WhatsappConsent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WhatsappConsentController extends Controller
{
    public function optIn(Request $request, int $boleto): \Illuminate\Http\Response
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Link de consentimento inválido ou expirado.');
        }

        $boleto = Boleto::find($boleto);

        if (! $boleto || ! $boleto->payer_document || ! $boleto->payer_phone) {
            abort(404, 'Boleto não encontrado ou dados incompletos.');
        }

        WhatsappConsent::grantConsent(
            tenantId: $boleto->tenant_id,
            document: $boleto->payer_document,
            phone:    $boleto->payer_phone,
            ip:       $request->ip(),
        );

        $tenantName = $boleto->tenant->email_entity_name ?? $boleto->tenant->name ?? 'Payproxy';

        return response(
            view('whatsapp.opt-in-success', compact('tenantName')),
            200
        );
    }
}

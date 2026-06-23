<?php

use App\Http\Controllers\Api\V1\BoletoController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

// API pública v1 — autenticada via API Key (RF-AC-14)
Route::prefix('v1')->name('api.v1.')->middleware(['api.key'])->group(function () {

    // Boletos (RF-01 a RF-08)
    Route::post('boletos', [BoletoController::class, 'store'])->name('boletos.store');
    Route::get('boletos/{boleto}', [BoletoController::class, 'show'])->name('boletos.show');
    Route::delete('boletos/{boleto}', [BoletoController::class, 'destroy'])->name('boletos.destroy');
});

// Webhooks recebidos dos parceiros bancários (sem autenticação API Key — verificação por HMAC no controller/middleware)
Route::prefix('webhooks')->name('api.webhooks.')->group(function () {
    Route::post('pjbank', [WebhookController::class, 'pjbank'])->name('pjbank');
});

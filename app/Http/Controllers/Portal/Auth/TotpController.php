<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TotpVerifyRequest;
use App\Models\TenantUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class TotpController extends Controller
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function show(): Response|RedirectResponse
    {
        if (! session()->has('portal.totp_pending_id')) {
            return redirect()->route('portal.auth.login.show');
        }

        return Inertia::render('Portal/Auth/Totp');
    }

    public function store(TotpVerifyRequest $request): RedirectResponse
    {
        $userId = $request->session()->get('portal.totp_pending_id');

        if (! $userId) {
            return redirect()->route('portal.auth.login.show');
        }

        $user   = TenantUser::findOrFail($userId);
        $secret = $user->getDecryptedTotpSecret();

        if (! $secret || ! $this->google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Código inválido ou expirado.']);
        }

        Auth::guard('portal')->login($user);

        $request->session()->forget('portal.totp_pending_id');
        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return redirect()->intended(route('portal.dashboard'));
    }

    public function setupShow(): Response|RedirectResponse
    {
        $user = Auth::guard('portal')->user();

        if ($user->hasTotpEnabled()) {
            return redirect()->route('portal.dashboard');
        }

        $secret = $this->google2fa->generateSecretKey();
        request()->session()->put('portal.totp_setup_secret', $secret);

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        return Inertia::render('Portal/Auth/TotpSetup', [
            'secret'    => $secret,
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    public function setupStore(TotpVerifyRequest $request): RedirectResponse
    {
        $user   = Auth::guard('portal')->user();
        $secret = $request->session()->get('portal.totp_setup_secret');

        if (! $secret || ! $this->google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Código inválido. Verifique o app autenticador.']);
        }

        $user->setEncryptedTotpSecret($secret);
        $user->totp_enabled      = true;
        $user->totp_confirmed_at = now();
        $user->save();

        $request->session()->forget('portal.totp_setup_secret');

        return redirect()->route('portal.dashboard')
            ->with('success', '2FA ativado com sucesso.');
    }
}

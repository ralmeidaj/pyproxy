<?php

namespace App\Http\Controllers\Backoffice\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TotpVerifyRequest;
use App\Models\BackofficeUser;
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
        if (! session()->has('backoffice.totp_pending_id')) {
            return redirect()->route('backoffice.auth.login.show');
        }

        return Inertia::render('Backoffice/Auth/Totp');
    }

    public function store(TotpVerifyRequest $request): RedirectResponse
    {
        $userId = $request->session()->get('backoffice.totp_pending_id');

        if (! $userId) {
            return redirect()->route('backoffice.auth.login.show');
        }

        $user = BackofficeUser::findOrFail($userId);
        $secret = $user->getDecryptedTotpSecret();

        if (! $secret || ! $this->google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Código inválido ou expirado.']);
        }

        Auth::guard('backoffice')->login($user);

        $request->session()->forget('backoffice.totp_pending_id');
        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return redirect()->intended(route('backoffice.dashboard'));
    }

    public function setupShow(): Response|RedirectResponse
    {
        $user = Auth::guard('backoffice')->user();

        if ($user->hasTotpEnabled()) {
            return redirect()->route('backoffice.dashboard');
        }

        $secret = $this->google2fa->generateSecretKey();
        $request = request();
        $request->session()->put('backoffice.totp_setup_secret', $secret);

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        return Inertia::render('Backoffice/Auth/TotpSetup', [
            'secret'    => $secret,
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    public function setupStore(TotpVerifyRequest $request): RedirectResponse
    {
        $user   = Auth::guard('backoffice')->user();
        $secret = $request->session()->get('backoffice.totp_setup_secret');

        if (! $secret || ! $this->google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Código inválido. Verifique o app autenticador.']);
        }

        $user->setEncryptedTotpSecret($secret);
        $user->totp_enabled      = true;
        $user->totp_confirmed_at = now();
        $user->save();

        $request->session()->forget('backoffice.totp_setup_secret');

        return redirect()->route('backoffice.dashboard')
            ->with('success', '2FA ativado com sucesso.');
    }
}

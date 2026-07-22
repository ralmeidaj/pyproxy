<?php

namespace Tests\Feature\Auth;

use App\Enums\TenantStatus;
use App\Models\BackofficeUser;
use App\Models\Tenant;
use App\Enums\CommunicationModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('');
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('');
        parent::tearDown();
    }

    public function test_backoffice_login_throttled_after_five_failures(): void
    {
        BackofficeUser::create([
            'name'     => 'Admin',
            'email'    => 'admin@payproxy.test',
            'password' => Hash::make('correct-password'),
            'role'     => 'admin',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('backoffice.auth.login.store'), [
                'email'    => 'admin@payproxy.test',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('backoffice.auth.login.store'), [
            'email'    => 'admin@payproxy.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_portal_login_throttled_after_five_failures(): void
    {
        Tenant::create([
            'name'                => 'Tenant Throttle Test',
            'document'            => '12345678000195',
            'email'               => 'tenant@throttle.test',
            'status'              => TenantStatus::Active,
            'communication_model' => CommunicationModel::Email,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('portal.auth.login.store'), [
                'email'    => 'user@throttle.test',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('portal.auth.login.store'), [
            'email'    => 'user@throttle.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_successful_login_not_blocked_before_limit(): void
    {
        BackofficeUser::create([
            'name'     => 'Admin OK',
            'email'    => 'adminok@payproxy.test',
            'password' => Hash::make('correct-password'),
            'role'     => 'admin',
        ]);

        // Apenas 4 falhas — não deve bloquear
        for ($i = 0; $i < 4; $i++) {
            $this->post(route('backoffice.auth.login.store'), [
                'email'    => 'adminok@payproxy.test',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('backoffice.auth.login.store'), [
            'email'    => 'adminok@payproxy.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(302); // Redirect, não 429
    }
}

<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\TenantIpAllowlistMiddleware;
use App\Models\Tenant;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery;
use Tests\TestCase;

class TenantIpAllowlistMiddlewareTest extends TestCase
{
    private AuditLogService $auditLog;
    private TenantIpAllowlistMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditLog   = Mockery::mock(AuditLogService::class);
        $this->middleware = new TenantIpAllowlistMiddleware($this->auditLog);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function requestWithTenant(string $ip, ?array $allowedIps): Request
    {
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => $ip]);
        $tenant  = Tenant::make(['allowed_ips' => $allowedIps]);
        $tenant->id = 1;
        $request->attributes->set('tenant', $tenant);
        return $request;
    }

    public function test_allows_when_no_tenant(): void
    {
        $request = Request::create('/');
        $called  = false;

        $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok');
        });

        $this->assertTrue($called);
    }

    public function test_allows_when_allowed_ips_is_null(): void
    {
        $request = $this->requestWithTenant('1.2.3.4', null);
        $called  = false;

        $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok');
        });

        $this->assertTrue($called);
    }

    public function test_allows_when_allowed_ips_is_empty(): void
    {
        $request = $this->requestWithTenant('1.2.3.4', []);
        $called  = false;

        $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok');
        });

        $this->assertTrue($called);
    }

    public function test_allows_ip_in_exact_list(): void
    {
        $request = $this->requestWithTenant('189.6.1.10', ['189.6.1.10', '10.0.0.1']);
        $called  = false;

        $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok');
        });

        $this->assertTrue($called);
    }

    public function test_allows_ip_matching_cidr(): void
    {
        $request = $this->requestWithTenant('189.6.1.55', ['189.6.1.0/24']);
        $called  = false;

        $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok');
        });

        $this->assertTrue($called);
    }

    public function test_blocks_ip_not_in_list(): void
    {
        $this->auditLog->shouldReceive('record')->once();

        $request  = $this->requestWithTenant('99.9.9.9', ['189.6.1.10', '10.0.0.1']);
        $response = $this->middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_blocks_ip_outside_cidr(): void
    {
        $this->auditLog->shouldReceive('record')->once();

        $request  = $this->requestWithTenant('192.168.2.1', ['192.168.1.0/24']);
        $response = $this->middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_allows_ip_in_slash_32_cidr(): void
    {
        $request = $this->requestWithTenant('10.0.0.1', ['10.0.0.1/32']);
        $called  = false;

        $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok');
        });

        $this->assertTrue($called);
    }
}

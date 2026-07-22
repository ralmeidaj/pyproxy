<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    private SecurityHeadersMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeadersMiddleware();
    }

    private function throughMiddleware(Request $request): Response
    {
        $response = new Response('ok');
        return $this->middleware->handle($request, fn () => $response);
    }

    public function test_adds_x_frame_options(): void
    {
        $response = $this->throughMiddleware(new Request());
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function test_adds_x_content_type_options(): void
    {
        $response = $this->throughMiddleware(new Request());
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_adds_referrer_policy(): void
    {
        $response = $this->throughMiddleware(new Request());
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
    }

    public function test_adds_permissions_policy(): void
    {
        $response = $this->throughMiddleware(new Request());
        $this->assertSame('geolocation=(), microphone=(), camera=()', $response->headers->get('Permissions-Policy'));
    }

    public function test_adds_hsts(): void
    {
        $response = $this->throughMiddleware(new Request());
        $this->assertSame(
            'max-age=31536000; includeSubDomains; preload',
            $response->headers->get('Strict-Transport-Security')
        );
    }

    public function test_adds_content_security_policy(): void
    {
        $response = $this->throughMiddleware(new Request());
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    public function test_passes_response_body_through(): void
    {
        $response = $this->throughMiddleware(new Request());
        $this->assertSame('ok', $response->getContent());
    }
}

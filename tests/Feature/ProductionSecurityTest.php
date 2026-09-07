<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionSecurityTest extends TestCase
{
    public function test_safe_http_request_is_permanently_redirected_to_https_in_production(): void
    {
        config(['app.env' => 'production']);

        $this->get('/?asal=http')
            ->assertStatus(308)
            ->assertRedirect('https://localhost:8000?asal=http');
    }

    public function test_unsafe_http_request_is_rejected_in_production(): void
    {
        config(['app.env' => 'production']);

        $this->post('/login', [])->assertBadRequest();
    }

    public function test_secure_production_response_includes_hsts_and_browser_security_headers(): void
    {
        config(['app.env' => 'production']);

        $this->get('https://localhost/')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
            ->assertHeader('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()')
            ->assertHeader('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.bunny.net; img-src 'self' data: blob: https://*.tile.openstreetmap.fr; font-src 'self' https://fonts.bunny.net; connect-src 'self' ws: wss: https://nominatim.openstreetmap.org; media-src 'self' blob:; frame-ancestors 'self'; base-uri 'self'; form-action 'self'")
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}

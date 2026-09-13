<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionCorsPolicyTest extends TestCase
{
    private const FRONTEND_ORIGIN = 'https://laras.bakaranproject.com';

    public function test_allowed_frontend_origin_receives_credentialed_cors_headers(): void
    {
        config()->set('cors.allowed_origins', [self::FRONTEND_ORIGIN]);
        config()->set('cors.supports_credentials', true);

        $response = $this
            ->withHeaders([
                'Origin' => self::FRONTEND_ORIGIN,
                'Accept' => 'application/json',
            ])
            ->get('/api/v1/me');

        $response->assertStatus(401);
        $response->assertHeader('Access-Control-Allow-Origin', self::FRONTEND_ORIGIN);
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_unapproved_origin_is_not_reflected(): void
    {
        config()->set('cors.allowed_origins', [self::FRONTEND_ORIGIN]);
        config()->set('cors.supports_credentials', true);

        $response = $this
            ->withHeaders([
                'Origin' => 'https://evil.example',
                'Accept' => 'application/json',
            ])
            ->get('/api/v1/me');

        $response->assertStatus(401);
        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_preflight_accepts_stateful_and_concurrency_headers_for_laras(): void
    {
        config()->set('cors.allowed_origins', [self::FRONTEND_ORIGIN]);
        config()->set('cors.allowed_methods', ['*']);
        config()->set('cors.allowed_headers', ['*']);
        config()->set('cors.supports_credentials', true);

        $response = $this->call('OPTIONS', '/api/v1/me', [], [], [], [
            'HTTP_ORIGIN' => self::FRONTEND_ORIGIN,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'X-XSRF-TOKEN, If-Match',
        ]);

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', self::FRONTEND_ORIGIN);
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');

        $allowedHeaders = strtolower((string) $response->headers->get('Access-Control-Allow-Headers'));
        $this->assertStringContainsString('x-xsrf-token', $allowedHeaders);
        $this->assertStringContainsString('if-match', $allowedHeaders);
    }

    public function test_required_response_headers_are_exposed_to_browser_clients(): void
    {
        $headers = array_map('strtolower', config('cors.exposed_headers', []));

        $this->assertContains('etag', $headers);
        $this->assertContains('retry-after', $headers);
        $this->assertContains('content-disposition', $headers);
    }
}

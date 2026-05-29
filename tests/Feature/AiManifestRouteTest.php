<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AiManifestRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai-manifest.cache_ttl', 0);
        config()->set('ai-manifest.name', 'TestApp');
        config()->set('ai-manifest.description', '');
        config()->set('ai-manifest.version', '1.0');
        config()->set('ai-manifest.auth', []);
        config()->set('ai-manifest.mechanisms', []);
        config()->set('ai-manifest.rate_limits', null);
        config()->set('ai-manifest.contact', null);
    }

    public function test_well_known_route_returns_200_json_with_capabilities_key(): void
    {
        $response = $this->get('/.well-known/ai-manifest.json');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $this->assertArrayHasKey('capabilities', $response->json());
    }

    public function test_marked_route_appears_in_manifest_output(): void
    {
        Route::get('/api/products', fn () => 'x')
            ->name('products.index')
            ->aiManifest()
            ->summary('Produkte auflisten');

        $response = $this->get('/.well-known/ai-manifest.json');
        $response->assertStatus(200);

        $capabilities = $response->json('capabilities');
        $ids = array_column($capabilities, 'id');

        $this->assertContains('products.index', $ids);

        $entry = array_values(array_filter($capabilities, fn ($c) => $c['id'] === 'products.index'))[0];
        $this->assertSame('Produkte auflisten', $entry['summary']);
    }
}

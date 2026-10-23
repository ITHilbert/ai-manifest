<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use ITHilbert\AiManifest\Services\AiManifestService;
use ITHilbert\AiManifest\Tests\TestCase;

final class AiManifestCacheFingerprintTest extends TestCase
{
    private AiManifestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai-manifest.name', 'TestApp');
        config()->set('ai-manifest.description', '');
        config()->set('ai-manifest.version', '1.0');
        config()->set('ai-manifest.auth', []);
        config()->set('ai-manifest.mechanisms', []);
        config()->set('ai-manifest.rate_limits', null);
        config()->set('ai-manifest.contact', null);
        config()->set('ai-manifest.cache_ttl', 0);

        $this->service = new AiManifestService;
    }

    public function test_fingerprint_is_deterministic_without_changes(): void
    {
        Route::get('/api/stable', fn () => 'x')
            ->name('stable.index')
            ->aiManifest()
            ->summary('Stabile Route');

        $hashFirst = $this->service->manifestFingerprint();
        $hashSecond = $this->service->manifestFingerprint();

        $this->assertSame($hashFirst, $hashSecond);
        $this->assertNotEmpty($hashFirst);
    }

    public function test_fingerprint_changes_when_new_route_is_registered(): void
    {
        Route::get('/api/original', fn () => 'x')
            ->name('original.index')
            ->aiManifest()
            ->summary('Original');

        $hashBefore = $this->service->manifestFingerprint();

        Route::get('/api/added', fn () => 'x')
            ->name('added.index')
            ->aiManifest()
            ->summary('Neu hinzugefügt');

        $hashAfter = $this->service->manifestFingerprint();

        $this->assertNotSame($hashBefore, $hashAfter);
    }

    public function test_fingerprint_changes_when_head_config_changes(): void
    {
        Route::get('/api/stable', fn () => 'x')
            ->name('stable.index')
            ->aiManifest()
            ->summary('Stabile Route');

        $hashBefore = $this->service->manifestFingerprint();

        // Kopf-Änderung OHNE Capability-Änderung
        config()->set('ai-manifest.description', 'Neue Beschreibung');

        $hashAfter = $this->service->manifestFingerprint();

        $this->assertNotSame(
            $hashBefore,
            $hashAfter,
            'Fingerprint muss sich auch bei reinen Kopf-Änderungen (description/mechanisms) ändern.'
        );
    }

    public function test_cache_invalidates_when_head_config_changes(): void
    {
        config()->set('ai-manifest.cache_ttl', 3600);
        config()->set('ai-manifest.mechanisms', []);
        Cache::flush();

        Route::get('/api/endpoint', fn () => 'x')
            ->name('endpoint.index')
            ->aiManifest()
            ->summary('Endpunkt');

        $first = $this->get('/.well-known/ai-manifest.json');
        $first->assertStatus(200);
        $this->assertEmpty($first->json('mechanisms') ?? []);

        // Reine Kopf-Änderung — Capabilities unverändert
        config()->set('ai-manifest.mechanisms', [
            ['type' => 'mcp', 'url' => '/mcp'],
        ]);

        $second = $this->get('/.well-known/ai-manifest.json');
        $second->assertStatus(200);
        $this->assertSame('/mcp', $second->json('mechanisms.0.url'));
    }

    public function test_cache_invalidates_automatically_when_new_route_is_registered(): void
    {
        config()->set('ai-manifest.cache_ttl', 3600);
        Cache::flush();

        Route::get('/api/first', fn () => 'x')
            ->name('first.endpoint')
            ->aiManifest()
            ->summary('Erster Endpunkt');

        $responseFirst = $this->get('/.well-known/ai-manifest.json');
        $responseFirst->assertStatus(200);

        $idsFirst = array_column($responseFirst->json('capabilities'), 'id');
        $this->assertContains('first.endpoint', $idsFirst);
        $this->assertNotContains('second.endpoint', $idsFirst);

        Route::get('/api/second', fn () => 'x')
            ->name('second.endpoint')
            ->aiManifest()
            ->summary('Zweiter Endpunkt');

        $responseSecond = $this->get('/.well-known/ai-manifest.json');
        $responseSecond->assertStatus(200);

        $idsSecond = array_column($responseSecond->json('capabilities'), 'id');
        $this->assertContains('first.endpoint', $idsSecond);
        $this->assertContains('second.endpoint', $idsSecond);
    }
}

<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Event;
use ITHilbert\AiManifest\Facades\AiManifest;
use ITHilbert\AiManifest\Tests\TestCase;

/**
 * AK11: Cache des ARD-Bodys. Beobachtet wird, welche Cache-Eintraege ein Abruf schreibt (Ereignis KeyWritten);
 * der Schluessel enthaelt den Fingerabdruck des Bodys, also aendert er sich mit dem Body.
 */
final class AiManifestCacheFingerprintTest extends TestCase
{
    /** @var list<KeyWritten> */
    private array $geschrieben = [];

    private string $summary = 'Erste Fassung';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai-manifest.publisher', 'shop.example');
        AiManifest::addGenerator(fn () => [['id' => 'g', 'path' => '/api/g', 'summary' => $this->summary]]);
        Event::listen(KeyWritten::class, function (KeyWritten $e): void {
            $this->geschrieben[] = $e;
        });
    }

    // AK11
    public function test_ttl_cached_den_body_unter_fingerabdruck_und_geaenderte_capability_erscheint_sofort(): void
    {
        config()->set('ai-manifest.cache_ttl', 3600);

        $erste = $this->get('/.well-known/ard.json');
        $erste->assertOk();
        $this->assertCount(1, $this->geschrieben);
        $this->assertSame(3600, $this->geschrieben[0]->seconds);
        $this->assertSame('Erste Fassung', $erste->json('entries.0.displayName'));

        // gleicher Body: Treffer, nichts Neues geschrieben
        $this->get('/.well-known/ard.json')->assertOk();
        $this->assertCount(1, $this->geschrieben);

        // geaenderte Capability: neuer Fingerabdruck, neuer Schluessel, Body sofort neu
        $this->summary = 'Zweite Fassung';
        $zweite = $this->get('/.well-known/ard.json');
        $this->assertSame('Zweite Fassung', $zweite->json('entries.0.displayName'));
        $this->assertCount(2, $this->geschrieben);
        $this->assertNotSame($this->geschrieben[0]->key, $this->geschrieben[1]->key);

        // auch eine reine Aenderung der Config (publisher) aendert den Body und den Schluessel
        config()->set('ai-manifest.publisher', 'anders.example');
        $dritte = $this->get('/.well-known/ard.json');
        $this->assertStringContainsString('anders.example', (string) $dritte->json('entries.0.identifier'));
        $this->assertCount(3, $this->geschrieben);
    }

    // AK11
    public function test_ttl_null_schreibt_keinen_cache(): void
    {
        config()->set('ai-manifest.cache_ttl', 0);

        $this->assertSame('Erste Fassung', $this->get('/.well-known/ard.json')->assertOk()->json('entries.0.displayName'));
        $this->summary = 'Zweite Fassung';
        $this->assertSame('Zweite Fassung', $this->get('/.well-known/ard.json')->json('entries.0.displayName'));

        $this->assertSame([], $this->geschrieben);
    }
}

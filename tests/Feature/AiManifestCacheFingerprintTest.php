<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use ITHilbert\AiManifest\Ard\ArdManifest;
use ITHilbert\AiManifest\Facades\AiManifest;
use ITHilbert\AiManifest\Tests\TestCase;

/**
 * AK11: kein Cache des ARD-Bodys. Beobachtet wird, welche Cache-Eintraege ein Abruf schreibt (Ereignis KeyWritten).
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
    public function test_abruf_schreibt_keinen_cache_eintrag_auch_nicht_bei_gesetztem_cache_ttl(): void
    {
        // Gegenprobe: die Beobachtung per KeyWritten greift
        Cache::put('gegenprobe', 1, 60);
        $this->assertCount(1, $this->geschrieben);
        $this->geschrieben = [];

        $this->get('/.well-known/ard.json')->assertOk();
        $this->assertSame([], $this->geschrieben);

        // auch ein (entfallener) Schluessel in der Config bringt keinen Cache zurueck
        config()->set('ai-manifest.cache_ttl', 3600);
        $this->get('/.well-known/ard.json')->assertOk();
        $this->assertSame([], $this->geschrieben);
    }

    // AK11
    public function test_geaenderte_capability_und_config_erscheinen_beim_naechsten_abruf_sofort(): void
    {
        config()->set('ai-manifest.cache_ttl', 3600);

        $this->assertSame('Erste Fassung', $this->get('/.well-known/ard.json')->assertOk()->json('entries.0.displayName'));

        $this->summary = 'Zweite Fassung';
        $this->assertSame('Zweite Fassung', $this->get('/.well-known/ard.json')->json('entries.0.displayName'));

        config()->set('ai-manifest.publisher', 'anders.example');
        $this->assertStringContainsString('anders.example', (string) $this->get('/.well-known/ard.json')->json('entries.0.identifier'));
    }

    // AK11
    public function test_config_kennt_cache_ttl_nicht_und_fingerabdruck_entfaellt(): void
    {
        $standard = require __DIR__.'/../../src/Config/ai-manifest.php';
        $this->assertArrayHasKey('publisher', $standard); // Gegenprobe: die Config-Datei ist gelesen
        $this->assertArrayNotHasKey('cache_ttl', $standard);
        $this->assertNull(config('ai-manifest.cache_ttl'));
        $this->assertFalse(method_exists(ArdManifest::class, 'fingerabdruck'));
        $this->assertTrue(method_exists(ArdManifest::class, 'body'));
    }
}

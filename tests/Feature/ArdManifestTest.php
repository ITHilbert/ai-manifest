<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ITHilbert\AiManifest\Ard\ArdFormat;
use ITHilbert\AiManifest\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Schnittstelle fuer den Coder (nur was die Spec nicht vorgibt):
 * - Config wird beim Booten des Providers gelesen (middleware); die Tests setzen sie in defineEnvironment.
 * - AK7: der alte env-Name AI_MANIFEST_LEGACY_AI_CATALOG und der alte Config-Schluessel werden gesetzt und duerfen nichts mehr bewirken.
 * - Config `llms_txt` ist ein Array mit den Schluesseln datei, inhalt, links (Standard null, null, []).
 * - Der Body von ard.json ist ein JSON-Objekt; ohne Eintraege exakt {"entries":[]} (Liste, kein Objekt).
 */
final class ArdManifestTest extends TestCase
{
    private const ENV_LEGACY = 'AI_MANIFEST_LEGACY_AI_CATALOG';

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('ai-manifest.middleware', [ArdTestMarkierer::class]);
        // llms.txt konfiguriert, damit auch diese Route eine 200-Antwort mit Markierung liefert
        $app['config']->set('ai-manifest.llms_txt', ['datei' => null, 'inhalt' => '# Test', 'links' => []]);

        if ($this->name() === 'test_ai_catalog_json_ist_404_trotz_gesetzter_env_und_config') {
            putenv(self::ENV_LEGACY.'=true');
            $app['config']->set('ai-manifest.ai_catalog_legacy', true);
        }
    }

    protected function tearDown(): void
    {
        putenv(self::ENV_LEGACY);
        parent::tearDown();
    }

    // AK1
    public function test_ard_route_liefert_json_mit_entries_und_content_type_und_name(): void
    {
        $response = $this->get('/.well-known/ard.json');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/ai-catalog+json; charset=utf-8');
        $this->assertIsArray($response->json('entries'));
        $this->assertSame('/.well-known/ard.json', route('ai-manifest.ard', [], false));
    }

    // AK1
    public function test_altes_manifest_ist_404_und_middleware_gilt_fuer_alle_routen(): void
    {
        // Gegenprobe: die neue Route ist erreichbar und trägt die Middleware
        $this->get('/.well-known/ard.json')->assertOk()->assertHeader('X-Ard-Test', '1');

        $this->get('/.well-known/ai-manifest.json')->assertNotFound();

        // llms.txt hat dieselbe Middleware
        $this->get('/llms.txt')->assertOk()->assertHeader('X-Ard-Test', '1');

        // Gegenprobe: eine fremde Route des Hosts bekommt die Markierung nicht
        Route::get('/fremd', fn () => 'x');
        $this->get('/fremd')->assertOk()->assertHeaderMissing('X-Ard-Test');
    }

    // AK5 (leere Website)
    public function test_ohne_routen_generatoren_und_eintraege_ist_der_body_eine_leere_liste(): void
    {
        $response = $this->get('/.well-known/ard.json');

        $response->assertOk();
        $this->assertSame('{"entries":[]}', preg_replace('/\s+/', '', (string) $response->getContent()));
    }

    // AK7
    public function test_ai_catalog_json_ist_404_trotz_gesetzter_env_und_config(): void
    {
        // Gegenprobe: ard.json ist erreichbar, der alte Pfad nicht
        $this->get('/.well-known/ard.json')->assertOk();

        $this->get('/.well-known/ai-catalog.json')->assertNotFound();
    }

    // AK7
    public function test_route_konstante_und_config_schluessel_des_uebergangspfads_gibt_es_nicht(): void
    {
        // Gegenprobe: die ARD-Route und ihre Konstante existieren
        $this->assertTrue(Route::has('ai-manifest.ard'));
        $this->assertTrue(defined(ArdFormat::class.'::PFAD_ARD'));

        $this->assertFalse(Route::has('ai-manifest.ai-catalog'));
        $this->assertFalse(defined(ArdFormat::class.'::PFAD_AI_CATALOG'));

        $standard = require __DIR__.'/../../src/Config/ai-manifest.php';
        $this->assertArrayHasKey('middleware', $standard);
        $this->assertArrayNotHasKey('ai_catalog_legacy', $standard);
    }
}

final class ArdTestMarkierer
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Ard-Test', '1');

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ITHilbert\AiManifest\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Schnittstelle fuer den Coder (nur was die Spec nicht vorgibt):
 * - Config wird beim Booten des Providers gelesen (middleware, ai_catalog_legacy); die Tests setzen sie
 *   in defineEnvironment bzw. nicht zur Laufzeit; der env-Name AI_MANIFEST_LEGACY_AI_CATALOG wird an der Config-Datei geprueft.
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

        if ($this->name() === 'test_config_false_schaltet_uebergangspfad_ab') {
            $app['config']->set('ai-manifest.ai_catalog_legacy', false);
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

        // Randfall 4: Uebergangspfad und llms.txt haben dieselbe Middleware
        $this->get('/.well-known/ai-catalog.json')->assertOk()->assertHeader('X-Ard-Test', '1');
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
    public function test_uebergangspfad_liefert_denselben_body_und_content_type(): void
    {
        Route::get('/api/items', fn () => 'x')->name('items.index')->aiManifest()->summary('Items');

        $ard = $this->get('/.well-known/ard.json');
        $legacy = $this->get('/.well-known/ai-catalog.json');

        $legacy->assertOk();
        $this->assertNotEmpty($ard->json('entries'));
        $this->assertSame($ard->json(), $legacy->json());
        $this->assertSame($ard->headers->get('Content-Type'), $legacy->headers->get('Content-Type'));
        $this->assertSame('/.well-known/ai-catalog.json', route('ai-manifest.ai-catalog', [], false));
    }

    // AK7
    public function test_config_false_schaltet_uebergangspfad_ab(): void
    {
        // Gegenprobe: ard.json bleibt erreichbar
        $this->get('/.well-known/ard.json')->assertOk();

        $this->get('/.well-known/ai-catalog.json')->assertNotFound();
    }

    // AK7
    public function test_env_name_steuert_ai_catalog_legacy_in_der_config_datei(): void
    {
        $datei = __DIR__.'/../../src/Config/ai-manifest.php';

        putenv(self::ENV_LEGACY);
        $this->assertTrue((require $datei)['ai_catalog_legacy']);

        putenv(self::ENV_LEGACY.'=false');
        $this->assertFalse((require $datei)['ai_catalog_legacy']);

        putenv(self::ENV_LEGACY.'=true');
        $this->assertTrue((require $datei)['ai_catalog_legacy']);
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

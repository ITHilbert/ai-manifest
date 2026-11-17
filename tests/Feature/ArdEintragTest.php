<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use Illuminate\Support\Facades\Route;
use ITHilbert\AiManifest\Enums\Mechanism;
use ITHilbert\AiManifest\Facades\AiManifest;
use ITHilbert\AiManifest\Services\AiManifestService;
use ITHilbert\AiManifest\Tests\Concerns\ArdAbruf;
use ITHilbert\AiManifest\Tests\TestCase;

/**
 * Schnittstelle fuer den Coder (nur was die Spec nicht vorgibt):
 * - Alles geht ueber GET /.well-known/ard.json, Config `ai-manifest.*` (zur Laufzeit gelesen),
 *   `AiManifest::addGenerator`, `->queries([...])` an der Route; keine internen Klassennamen.
 * - Reihenfolge der Eintraege ist nicht festgelegt; Tests suchen per identifier.
 * - Der Eintragsschluessel `name` eines manuellen Eintrags darf im Ausgabe-Eintrag stehen oder fehlen.
 */
final class ArdEintragTest extends TestCase
{
    use ArdAbruf;

    private const TYP = 'application/vnd.ithilbert.ai-manifest-capability+json';

    private const IDENTIFIER_MUSTER = '/^urn:air:[a-zA-Z0-9.-]+(:[a-zA-Z0-9._-]+)+$/';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai-manifest.publisher', 'shop.example');
    }

    /**
     * Nachbau der Pflichten aus ard-entry.schema.json (kein JSON-Schema-Paket vorhanden).
     *
     * @param  array<string, mixed>  $eintrag
     * @return list<string> Verstoesse
     */
    private function schemaVerstoesse(array $eintrag): array
    {
        $fehler = [];

        if (! isset($eintrag['identifier']) || ! is_string($eintrag['identifier'])
            || preg_match(self::IDENTIFIER_MUSTER, $eintrag['identifier']) !== 1) {
            $fehler[] = 'identifier';
        }
        foreach (['displayName', 'type'] as $feld) {
            if (! isset($eintrag[$feld]) || ! is_string($eintrag[$feld])) {
                $fehler[] = $feld;
            }
        }
        if (array_key_exists('url', $eintrag) === array_key_exists('data', $eintrag)) {
            $fehler[] = 'url/data genau eines';
        }
        if (isset($eintrag['url']) && ! is_string($eintrag['url'])) {
            $fehler[] = 'url';
        }
        if (isset($eintrag['data']) && ! is_array($eintrag['data'])) {
            $fehler[] = 'data';
        }
        if (isset($eintrag['representativeQueries'])) {
            $q = $eintrag['representativeQueries'];
            if (! is_array($q) || ! array_is_list($q) || array_filter($q, fn ($x) => ! is_string($x)) !== []) {
                $fehler[] = 'representativeQueries';
            }
        }

        return $fehler;
    }

    // AK2
    public function test_markierte_route_wird_ein_eintrag_mit_capability_in_data(): void
    {
        Route::post('/api/domains', fn () => 'x')
            ->name('domains.create')
            ->aiManifest()
            ->summary('Domain erstellen')
            ->auth()
            ->param('name', 'string', true, 'Domain-Name')
            ->mechanism(Mechanism::HttpApi);
        Route::get('/api/domains/{id}', fn () => 'x')->name('domains.show')->aiManifest();
        Route::get('/api/ohne-name', fn () => 'x')->aiManifest();
        Route::get('/intern', fn () => 'x')->name('intern');

        $eintraege = $this->ardEintraege();
        $this->assertCount(3, $eintraege, 'nur markierte Routen erscheinen');

        $voll = $this->ardEintrag('domains.create');
        $this->assertSame('urn:air:shop.example:routes:domains.create', $voll['identifier']);
        $this->assertSame('Domain erstellen', $voll['displayName']);
        $this->assertSame(self::TYP, $voll['type']);
        $this->assertArrayNotHasKey('url', $voll);
        $this->assertSame([
            'id' => 'domains.create',
            'method' => 'POST',
            'path' => '/api/domains',
            'summary' => 'Domain erstellen',
            'auth' => true,
            'params' => ['name' => ['type' => 'string', 'required' => true, 'desc' => 'Domain-Name']],
            'mechanism' => 'http_api',
        ], $voll['data']);

        // ohne Summary: displayName = id; auth/params/mechanism fehlen; Pfad mit Parameter
        $schlicht = $this->ardEintrag('domains.show');
        $this->assertSame('domains.show', $schlicht['displayName']);
        $this->assertSame('/api/domains/{id}', $schlicht['data']['path']);
        $this->assertSame('GET', $schlicht['data']['method']);
        foreach (['auth', 'params', 'mechanism'] as $feld) {
            $this->assertArrayNotHasKey($feld, $schlicht['data']);
        }
        $this->assertSame('get:/api/ohne-name', $this->ardEintrag('get--api-ohne-name')['data']['id']);
    }

    // AK2
    public function test_generator_eintrag_wird_ein_eintrag_und_ungueltige_werden_verworfen(): void
    {
        AiManifest::addGenerator(fn () => [
            ['id' => 'gen.eins', 'path' => '/api/gen', 'method' => 'put', 'summary' => 'Generiert', 'auth' => true],
            ['path' => '/api/ohne-id'],
            ['id' => 'ohne.pfad'],
        ]);

        $eintraege = $this->ardEintraege();
        $this->assertCount(1, $eintraege);
        $this->assertSame('urn:air:shop.example:routes:gen.eins', $eintraege[0]['identifier']);
        $this->assertSame('Generiert', $eintraege[0]['displayName']);
        $this->assertSame(self::TYP, $eintraege[0]['type']);
        $this->assertSame('PUT', $eintraege[0]['data']['method']);
        $this->assertTrue($eintraege[0]['data']['auth']);

        // clearGenerators entfernt die Eintraege wieder
        app(AiManifestService::class)->clearGenerators();
        $this->assertSame([], $this->ardEintraege());
    }

    // AK2 + Randfall 1
    public function test_id_zeichen_ausserhalb_des_alphabets_werden_je_zu_minus(): void
    {
        Route::get('/api/foo', fn () => 'x')->aiManifest()->id('a b:c/d');
        Route::get('/api/foo/bar', fn () => 'x')->aiManifest();

        $this->assertSame('a b:c/d', $this->ardEintrag('a-b-c-d')['data']['id']);

        // Randfall 1: ohne Summary und Namen id "get:/pfad"; im identifier werden ":" und "/" zu "-"
        $ohneName = $this->ardEintrag('get--api-foo-bar');
        $this->assertSame('get:/api/foo/bar', $ohneName['data']['id']);
        $this->assertSame('get:/api/foo/bar', $ohneName['displayName']);
        $this->assertSame('urn:air:shop.example:routes:get--api-foo-bar', $ohneName['identifier']);
    }

    // AK3
    public function test_representative_queries_aus_queries_begrenzt_bereinigt_und_mit_ersatz(): void
    {
        Route::get('/api/viele', fn () => 'x')->name('viele')->aiManifest()
            ->queries(['a', 'b', 'c', 'd', 'e', 'f', 'a', '']);
        Route::get('/api/bereinigt', fn () => 'x')->name('bereinigt')->aiManifest()
            ->queries(['', 'x', 'x', 'y']);
        Route::get('/api/wenig', fn () => 'x')->name('wenig')->aiManifest()->summary('Wenig')->queries(['nur eine']);
        Route::get('/api/keine', fn () => 'x')->name('keine')->aiManifest()->summary('Keine');
        AiManifest::addGenerator(fn () => [
            ['id' => 'gen', 'path' => '/api/gen', 'queries' => ['g1', 'g2', 'g1']],
        ]);

        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $this->ardEintrag('viele')['representativeQueries']);
        $this->assertSame(['x', 'y'], $this->ardEintrag('bereinigt')['representativeQueries']);
        $this->assertSame(['g1', 'g2'], $this->ardEintrag('gen')['representativeQueries']);

        // weniger als zwei: Ersatz aus displayName und "<METHOD> <path>"
        $this->assertSame(['Keine', 'GET /api/keine'], $this->ardEintrag('keine')['representativeQueries']);
        $this->assertGreaterThanOrEqual(2, count($this->ardEintrag('wenig')['representativeQueries']));
        $this->assertLessThanOrEqual(5, count($this->ardEintrag('wenig')['representativeQueries']));
    }

    // AK4
    public function test_publisher_aus_config_sonst_app_url_host_sonst_localhost(): void
    {
        Route::get('/api/x', fn () => 'x')->name('x')->aiManifest();

        $this->assertSame('urn:air:shop.example:routes:x', $this->ardEintrag('x')['identifier']);

        config()->set('ai-manifest.publisher', null);
        config()->set('app.url', 'https://Shop.Beispiel.COM:8443/pfad');
        $this->assertSame('urn:air:shop.beispiel.com:routes:x', $this->ardEintrag('x')['identifier']);

        config()->set('app.url', '');
        $this->assertSame('urn:air:localhost:routes:x', $this->ardEintrag('x')['identifier']);

        config()->set('ai-manifest.namespace', 'meine-api');
        $this->assertSame('urn:air:localhost:meine-api:x', $this->ardEintrag('x')['identifier']);
    }

    // AK4 + AK6
    public function test_alle_routen_eintraege_erfuellen_muster_und_schema_pflichten(): void
    {
        Route::get('/api/a', fn () => 'x')->name('a')->aiManifest()->summary('A')->queries(['q1', 'q2']);
        Route::post('/api/b/{id}', fn () => 'x')->aiManifest()->param('p', 'int');
        Route::get('/api/c', fn () => 'x')->aiManifest()->id('Ä ö ü:/ \\ %');
        AiManifest::addGenerator(fn () => [['id' => 'g.1', 'path' => '/g'], ['id' => 'g.1', 'path' => '/g2']]);

        $eintraege = $this->ardEintraege();
        $this->assertCount(5, $eintraege);
        foreach ($eintraege as $eintrag) {
            $this->assertSame([], $this->schemaVerstoesse($eintrag), json_encode($eintrag));
            $this->assertMatchesRegularExpression(self::IDENTIFIER_MUSTER, $eintrag['identifier']);
            $this->assertIsArray($eintrag['data']);
        }

        // Gegenprobe: der Nachbau erkennt Verstoesse
        $this->assertContains('type', $this->schemaVerstoesse(['identifier' => 'urn:air:a:b:c', 'displayName' => 'x', 'url' => 'u']));
        $this->assertContains('url/data genau eines', $this->schemaVerstoesse(['identifier' => 'urn:air:a:b:c', 'displayName' => 'x', 'type' => 't', 'url' => 'u', 'data' => []]));
        $this->assertContains('identifier', $this->schemaVerstoesse(['identifier' => 'urn:air:a', 'displayName' => 'x', 'type' => 't', 'url' => 'u']));
    }

    // AK5
    public function test_manuelle_eintraege_ergaenzen_und_unvollstaendige_werden_verworfen(): void
    {
        $gut = ['name' => 'llms', 'displayName' => 'llms.txt', 'type' => 'text/markdown', 'url' => 'https://shop.example/llms.txt',
            'representativeQueries' => ['Was bietet die Firma?', 'Wie erreiche ich sie?'], 'description' => 'Kurzbeschreibung', 'tags' => ['info']];
        config()->set('ai-manifest.entries', [
            $gut,
            ['name' => 'mitdata', 'displayName' => 'Daten', 'type' => 'application/json', 'data' => ['a' => 1]],
            ['displayName' => 'ohne name', 'type' => 't', 'url' => 'https://x.test'],
            ['name' => 'ohnedisplay', 'type' => 't', 'url' => 'https://x.test'],
            ['name' => 'ohnetype', 'displayName' => 'x', 'url' => 'https://x.test'],
            ['name' => 'ohneziel', 'displayName' => 'x', 'type' => 't'],
            ['name' => 'beides', 'displayName' => 'x', 'type' => 't', 'url' => 'https://x.test', 'data' => []],
        ]);

        $eintraege = $this->ardEintraege();
        $this->assertCount(2, $eintraege);

        $llms = $this->ardEintrag('llms');
        $this->assertSame('urn:air:shop.example:site:llms', $llms['identifier']);
        foreach (['displayName', 'type', 'url', 'representativeQueries', 'description', 'tags'] as $schluessel) {
            $this->assertSame($gut[$schluessel], $llms[$schluessel]);
        }
        $this->assertSame(['a' => 1], $this->ardEintrag('mitdata')['data']);

        config()->set('ai-manifest.entries_namespace', 'extra');
        $this->assertSame('urn:air:shop.example:extra:llms', $this->ardEintrag('llms')['identifier']);
    }

    // Randfall 2
    public function test_gleicher_identifier_bekommt_zaehler_angehaengt(): void
    {
        Route::get('/api/eins', fn () => 'x')->aiManifest()->id('dup');
        AiManifest::addGenerator(fn () => [['id' => 'dup', 'path' => '/api/zwei'], ['id' => 'dup', 'path' => '/api/drei']]);
        config()->set('ai-manifest.entries', [
            ['name' => 'm', 'displayName' => 'x', 'type' => 't', 'url' => 'https://x.test/1'],
            ['name' => 'm', 'displayName' => 'y', 'type' => 't', 'url' => 'https://x.test/2'],
        ]);

        $identifier = array_column($this->ardEintraege(), 'identifier');
        sort($identifier);

        $this->assertSame([
            'urn:air:shop.example:routes:dup',
            'urn:air:shop.example:routes:dup-2',
            'urn:air:shop.example:routes:dup-3',
            'urn:air:shop.example:site:m',
            'urn:air:shop.example:site:m-2',
        ], $identifier);
    }

    // AK14 (a)
    public function test_ersatz_queries_ergaenzen_id_und_path_bis_zwei_werte(): void
    {
        // displayName = "GET /api/k" faellt mit dem ersten Ersatzwert zusammen -> nur ein Wert
        Route::get('/api/k', fn () => 'x')->name('k')->aiManifest()->summary('GET /api/k');
        // Gegenprobe: ohne Kollision bleibt es bei displayName und "<METHOD> <path>"
        Route::get('/api/andere', fn () => 'x')->name('andere')->aiManifest()->summary('Andere');

        $this->assertSame(['GET /api/k', 'k'], $this->ardEintrag('k')['representativeQueries']);
        $this->assertSame(['Andere', 'GET /api/andere'], $this->ardEintrag('andere')['representativeQueries']);
    }

    // AK14 (b)
    public function test_leere_id_faellt_zurueck_und_generator_mit_leerer_id_oder_path_wird_verworfen(): void
    {
        Route::get('/api/leer', fn () => 'x')->name('leer.route')->aiManifest()->id('');
        Route::get('/api/ohnename', fn () => 'x')->aiManifest()->id('');
        AiManifest::addGenerator(fn () => [
            ['id' => 'gen.gut', 'path' => '/api/gut'],
            ['id' => '', 'path' => '/api/leere-id'],
            ['id' => 'gen.leerer.pfad', 'path' => ''],
        ]);

        $eintraege = $this->ardEintraege();
        $this->assertCount(3, $eintraege, json_encode($eintraege));
        $this->assertSame('leer.route', $this->ardEintrag('leer.route')['data']['id']);
        $this->assertSame('leer.route', $this->ardEintrag('leer.route')['displayName']);
        $this->assertSame('get:/api/ohnename', $this->ardEintrag('get--api-ohnename')['displayName']);
        $this->assertSame('gen.gut', $this->ardEintrag('gen.gut')['displayName']);
        foreach ($eintraege as $eintrag) {
            $this->assertNotSame('', $eintrag['displayName']);
        }
    }

    // AK14 (c)
    public function test_manueller_eintrag_mit_data_als_liste_wird_verworfen_und_leeres_data_bleibt_objekt(): void
    {
        config()->set('ai-manifest.entries', [
            ['name' => 'objekt', 'displayName' => 'Objekt', 'type' => 't', 'data' => ['a' => 1]],
            ['name' => 'leer', 'displayName' => 'Leer', 'type' => 't', 'data' => []],
            ['name' => 'liste', 'displayName' => 'Liste', 'type' => 't', 'data' => ['x', 'y']],
        ]);

        $identifier = array_column($this->ardEintraege(), 'identifier');
        sort($identifier);
        $this->assertSame([
            'urn:air:shop.example:site:leer',
            'urn:air:shop.example:site:objekt',
        ], $identifier);

        $body = $this->get('/.well-known/ard.json')->getContent();
        $this->assertStringContainsString('"data":{}', $body);
        $this->assertStringNotContainsString('"data":[]', $body);
    }

    // AK12
    public function test_config_traegt_neue_schluessel_und_nicht_mehr_die_alten(): void
    {
        $config = config('ai-manifest');

        // Standard von publisher (setUp ueberschreibt ihn) aus der Config-Datei des Pakets
        $standard = require __DIR__.'/../../src/Config/ai-manifest.php';
        $this->assertArrayHasKey('publisher', $standard);
        $this->assertNull($standard['publisher']);
        $this->assertSame('routes', $config['namespace']);
        $this->assertSame('site', $config['entries_namespace']);
        $this->assertSame([], $config['entries']);
        $this->assertTrue($config['ai_catalog_legacy']);
        $this->assertIsArray($config['llms_txt']);
        foreach (['datei', 'inhalt', 'links'] as $schluessel) {
            $this->assertArrayHasKey($schluessel, $config['llms_txt']);
        }
        foreach (['name', 'description', 'middleware'] as $bleibend) {
            $this->assertArrayHasKey($bleibend, $config);
        }
        // Gegenprobe oben: die Config ist geladen; cache_ttl entfaellt (auch im Standard der Config-Datei)
        $this->assertArrayNotHasKey('cache_ttl', $config);
        $this->assertArrayNotHasKey('cache_ttl', $standard);
        foreach (['version', 'auth', 'mechanisms', 'rate_limits', 'contact', 'public'] as $entfallen) {
            $this->assertArrayNotHasKey($entfallen, $config);
        }

        foreach (['toArray', 'toJson', 'buildHead'] as $methode) {
            $this->assertFalse(method_exists(AiManifestService::class, $methode), "{$methode}() muss entfallen");
        }
        $this->assertTrue(enum_exists(Mechanism::class));
    }
}

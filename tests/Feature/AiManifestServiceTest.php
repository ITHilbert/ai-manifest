<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use Illuminate\Support\Facades\Route;
use ITHilbert\AiManifest\Facades\AiManifest;
use ITHilbert\AiManifest\Tests\Concerns\ArdAbruf;
use ITHilbert\AiManifest\Tests\TestCase;

/**
 * Sammlung der Eintraege aus Routen und Generatoren, geprueft ueber ard.json
 * (vorher ueber getCapabilities()/toArray() des 1.x-Formats). Die Eintragsform steht in ArdEintragTest.
 */
final class AiManifestServiceTest extends TestCase
{
    use ArdAbruf;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai-manifest.cache_ttl', 0);
        config()->set('ai-manifest.publisher', 'shop.example');
    }

    // AK2
    public function test_route_ohne_markierung_wird_ignoriert(): void
    {
        Route::get('/intern', fn () => 'x')->name('intern');
        Route::get('/api/offen', fn () => 'x')->name('offen')->aiManifest();

        // Gegenprobe: die markierte Route erscheint
        $this->assertSame(['urn:air:shop.example:routes:offen'], array_column($this->ardEintraege(), 'identifier'));
    }

    // AK2
    public function test_generator_eintraege_ohne_id_oder_pfad_werden_verworfen_und_clear_entfernt_alle(): void
    {
        AiManifest::addGenerator(fn () => [
            ['path' => '/api/x', 'summary' => 'Ohne ID'],
            ['id' => 'no-path', 'summary' => 'Ohne Pfad'],
            ['id' => 'valid', 'path' => '/api/y'],
        ]);

        $this->assertSame(['urn:air:shop.example:routes:valid'], array_column($this->ardEintraege(), 'identifier'));

        AiManifest::clearGenerators();
        $this->assertSame([], $this->ardEintraege());
    }
}

<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use ITHilbert\AiManifest\Facades\AiManifest;
use ITHilbert\AiManifest\Tests\TestCase;

final class AiManifestGenerateCommandTest extends TestCase
{
    private string $verzeichnis;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai-manifest.cache_ttl', 0);
        config()->set('ai-manifest.publisher', 'shop.example');

        $this->verzeichnis = sys_get_temp_dir().'/ai-manifest-test-'.uniqid('', true);
        (new Filesystem)->makeDirectory($this->verzeichnis, 0755, true);

        Route::get('/api/items', fn () => 'x')->name('items.index')->aiManifest()->summary('Alle Items auflisten');
        Route::get('/api/more', fn () => 'x')->name('items.more')->aiManifest();
        AiManifest::addGenerator(fn () => [['id' => 'gen.eins', 'path' => '/api/gen']]);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->verzeichnis);
        parent::tearDown();
    }

    // AK10
    public function test_befehl_schreibt_body_von_ard_json_nach_path_und_meldet_die_anzahl(): void
    {
        $ziel = $this->verzeichnis.'/neu/ard.json';

        $exit = Artisan::call('ai-manifest:generate', ['--path' => $ziel]);

        $this->assertSame(0, $exit);
        $this->assertFileExists($ziel);
        $erwartet = $this->get('/.well-known/ard.json')->json();
        $this->assertCount(3, $erwartet['entries']);
        $this->assertSame($erwartet, json_decode((string) file_get_contents($ziel), true));
        $this->assertMatchesRegularExpression('/(?<![\w.-])3(?![\w.-])/', str_replace($ziel, '', Artisan::output()));
    }

    // AK10
    public function test_ohne_path_entsteht_ard_json_in_public_und_ai_manifest_json_nicht(): void
    {
        $this->app->usePublicPath($this->verzeichnis);

        $this->assertSame(0, Artisan::call('ai-manifest:generate'));

        $this->assertFileExists($this->verzeichnis.'/.well-known/ard.json');
        $this->assertFileDoesNotExist($this->verzeichnis.'/.well-known/ai-manifest.json');
    }
}

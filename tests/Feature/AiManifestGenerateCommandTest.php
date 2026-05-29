<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AiManifestGenerateCommandTest extends TestCase
{
    private string $tempFile;

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

        $this->tempFile = sys_get_temp_dir().'/ai-manifest-test-'.uniqid('', true).'/ai-manifest.json';
    }

    protected function tearDown(): void
    {
        $dir = dirname($this->tempFile);
        (new Filesystem)->deleteDirectory($dir);
        parent::tearDown();
    }

    public function test_command_writes_valid_json_file_with_capabilities(): void
    {
        Route::get('/api/items', fn () => 'x')
            ->name('items.index')
            ->aiManifest()
            ->summary('Alle Items auflisten');

        $exit = $this->artisan('ai-manifest:generate', ['--path' => $this->tempFile])->run();

        $this->assertSame(0, $exit);
        $this->assertFileExists($this->tempFile);

        $content = file_get_contents($this->tempFile);
        $this->assertIsString($content);

        $json = json_decode($content, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('capabilities', $json);
        $this->assertNotEmpty($json['capabilities']);

        $ids = array_column($json['capabilities'], 'id');
        $this->assertContains('items.index', $ids);
    }
}

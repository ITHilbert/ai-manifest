<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use Illuminate\Support\Facades\Route;
use ITHilbert\AiManifest\Data\CapabilityEntry;
use ITHilbert\AiManifest\Enums\Mechanism;
use ITHilbert\AiManifest\Services\AiManifestService;
use ITHilbert\AiManifest\Tests\TestCase;

final class AiManifestServiceTest extends TestCase
{
    private AiManifestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai-manifest.name', null);
        config()->set('ai-manifest.description', '');
        config()->set('ai-manifest.version', '1.0');
        config()->set('ai-manifest.auth', []);
        config()->set('ai-manifest.mechanisms', []);
        config()->set('ai-manifest.rate_limits', null);
        config()->set('ai-manifest.contact', null);
        config()->set('app.name', 'TestApp');

        $this->service = new AiManifestService;
    }

    public function test_route_without_meta_is_ignored(): void
    {
        Route::get('/intern', fn () => 'x');

        $capabilities = $this->service->getCapabilities();

        $this->assertSame([], $capabilities);
    }

    public function test_route_with_ai_manifest_becomes_capability(): void
    {
        Route::get('/api/domains', fn () => 'x')->name('domains.index')->aiManifest();

        $capabilities = $this->service->getCapabilities();

        $this->assertCount(1, $capabilities);
        $this->assertInstanceOf(CapabilityEntry::class, $capabilities[0]);
        $this->assertSame('domains.index', $capabilities[0]->id);
        $this->assertSame('GET', $capabilities[0]->method);
        $this->assertSame('/api/domains', $capabilities[0]->path);
    }

    public function test_post_route_is_captured_correctly(): void
    {
        Route::post('/api/domains', fn () => 'x')->name('domains.store')->aiManifest();

        $capabilities = $this->service->getCapabilities();

        $this->assertCount(1, $capabilities);
        $this->assertSame('POST', $capabilities[0]->method);
    }

    public function test_route_with_uri_param_is_included(): void
    {
        Route::get('/api/domains/{id}', fn () => 'x')->name('domains.show')->aiManifest();

        $capabilities = $this->service->getCapabilities();

        $this->assertCount(1, $capabilities);
        $this->assertSame('/api/domains/{id}', $capabilities[0]->path);
    }

    public function test_id_defaults_to_route_name(): void
    {
        Route::get('/api/foo', fn () => 'x')->name('foo.index')->aiManifest();

        $capabilities = $this->service->getCapabilities();

        $this->assertSame('foo.index', $capabilities[0]->id);
    }

    public function test_id_defaults_to_method_plus_path_when_no_name(): void
    {
        Route::get('/api/bar', fn () => 'x')->aiManifest();

        $capabilities = $this->service->getCapabilities();

        $this->assertSame('get:/api/bar', $capabilities[0]->id);
    }

    public function test_setters_summary_auth_param_mechanism_land_in_capability(): void
    {
        Route::post('/api/domains', fn () => 'x')
            ->name('domains.create')
            ->aiManifest()
            ->summary('Domain erstellen')
            ->auth(true)
            ->param('name', 'string', true, 'Domain-Name')
            ->mechanism(Mechanism::HttpApi);

        $capabilities = $this->service->getCapabilities();
        $entry = $capabilities[0];

        $this->assertSame('Domain erstellen', $entry->summary);
        $this->assertTrue($entry->auth);
        $this->assertCount(1, $entry->params);
        $this->assertSame('name', $entry->params[0]->name);
        $this->assertSame('string', $entry->params[0]->type);
        $this->assertTrue($entry->params[0]->required);
        $this->assertSame('Domain-Name', $entry->params[0]->desc);
        $this->assertSame(Mechanism::HttpApi, $entry->mechanism);
    }

    public function test_build_head_falls_back_to_app_name_when_config_name_is_null(): void
    {
        config()->set('ai-manifest.name', null);
        config()->set('app.name', 'MeineApp');

        $head = $this->service->buildHead();

        $this->assertSame('MeineApp', $head['name']);
    }

    public function test_build_head_uses_config_name_when_set(): void
    {
        config()->set('ai-manifest.name', 'KonkreterName');

        $head = $this->service->buildHead();

        $this->assertSame('KonkreterName', $head['name']);
    }

    public function test_to_array_contains_head_fields_and_capabilities(): void
    {
        config()->set('ai-manifest.version', '2.0');

        Route::get('/api/test', fn () => 'x')->name('test')->aiManifest()->summary('Test');

        $array = $this->service->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('capabilities', $array);
        $this->assertSame('2.0', $array['version']);
        $this->assertCount(1, $array['capabilities']);
        $this->assertSame('test', $array['capabilities'][0]['id']);
    }

    public function test_generator_callback_is_included(): void
    {
        $this->service->addGenerator(fn () => [
            ['id' => 'dynamic.one', 'path' => '/api/dynamic', 'method' => 'GET', 'summary' => 'Dynamisch'],
        ]);

        $capabilities = $this->service->getCapabilities();

        $this->assertCount(1, $capabilities);
        $this->assertSame('dynamic.one', $capabilities[0]->id);
        $this->assertSame('/api/dynamic', $capabilities[0]->path);
        $this->assertSame('GET', $capabilities[0]->method);
    }

    public function test_generator_entry_without_id_is_discarded(): void
    {
        $this->service->addGenerator(fn () => [
            ['path' => '/api/x', 'summary' => 'Ohne ID'],
            ['id' => 'valid', 'path' => '/api/y'],
        ]);

        $capabilities = $this->service->getCapabilities();

        $this->assertCount(1, $capabilities);
        $this->assertSame('valid', $capabilities[0]->id);
    }

    public function test_generator_entry_without_path_is_discarded(): void
    {
        $this->service->addGenerator(fn () => [
            ['id' => 'no-path', 'summary' => 'Ohne Pfad'],
            ['id' => 'valid', 'path' => '/api/z'],
        ]);

        $capabilities = $this->service->getCapabilities();

        $this->assertCount(1, $capabilities);
        $this->assertSame('valid', $capabilities[0]->id);
    }

    public function test_clear_generators(): void
    {
        $this->service->addGenerator(fn () => [
            ['id' => 'x', 'path' => '/x'],
        ]);
        $this->service->clearGenerators();

        $this->assertSame([], $this->service->getCapabilities());
    }
}

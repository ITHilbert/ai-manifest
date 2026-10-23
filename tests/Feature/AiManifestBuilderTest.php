<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use Illuminate\Support\Facades\Route;
use ITHilbert\AiManifest\Builders\AiManifestRouteBuilder;
use ITHilbert\AiManifest\Enums\Mechanism;
use ITHilbert\AiManifest\Tests\TestCase;

final class AiManifestBuilderTest extends TestCase
{
    public function test_ai_manifest_macro_returns_builder_and_invoke_returns_route(): void
    {
        $route = Route::get('/x', fn () => 'x');
        $builder = $route->aiManifest();

        $this->assertInstanceOf(AiManifestRouteBuilder::class, $builder);
        $this->assertSame($route, $builder());
    }

    public function test_summary_writes_to_meta(): void
    {
        $route = Route::get('/x', fn () => 'x');
        $route->aiManifest()->summary('Holt alle Einträge');

        $meta = $route->getAction()['ai_manifest_meta'];
        $this->assertSame('Holt alle Einträge', $meta['summary']);
    }

    public function test_auth_writes_to_meta(): void
    {
        $route = Route::get('/x', fn () => 'x');
        $route->aiManifest()->auth(true);

        $meta = $route->getAction()['ai_manifest_meta'];
        $this->assertTrue($meta['auth']);
    }

    public function test_param_appends_to_meta_params(): void
    {
        $route = Route::post('/x', fn () => 'x');
        $route->aiManifest()->param('name', 'string', true, 'Der Name');

        $meta = $route->getAction()['ai_manifest_meta'];
        $params = $meta['params'];

        $this->assertCount(1, $params);
        $this->assertSame('name', $params[0]['name']);
        $this->assertSame('string', $params[0]['type']);
        $this->assertTrue($params[0]['required']);
        $this->assertSame('Der Name', $params[0]['desc']);
    }

    public function test_mechanism_writes_normalized_value_to_meta(): void
    {
        $route = Route::get('/x', fn () => 'x');
        $route->aiManifest()->mechanism(Mechanism::HttpApi);

        $meta = $route->getAction()['ai_manifest_meta'];
        $this->assertSame('http_api', $meta['mechanism']);
    }

    public function test_mechanism_accepts_string(): void
    {
        $route = Route::get('/x', fn () => 'x');
        $route->aiManifest()->mechanism('mcp');

        $meta = $route->getAction()['ai_manifest_meta'];
        $this->assertSame('mcp', $meta['mechanism']);
    }

    public function test_mechanism_rejects_unknown_string(): void
    {
        $route = Route::get('/x', fn () => 'x');

        $this->expectException(\InvalidArgumentException::class);
        $route->aiManifest()->mechanism('graphql');
    }

    public function test_id_writes_to_meta(): void
    {
        $route = Route::get('/x', fn () => 'x');
        $route->aiManifest()->id('custom.id');

        $meta = $route->getAction()['ai_manifest_meta'];
        $this->assertSame('custom.id', $meta['id']);
    }

    public function test_array_options_are_merged_into_meta(): void
    {
        $route = Route::get('/x', fn () => 'x');
        $route->aiManifest(['summary' => 'Test', 'auth' => true]);

        $meta = $route->getAction()['ai_manifest_meta'];
        $this->assertSame('Test', $meta['summary']);
        $this->assertTrue($meta['auth']);
    }
}

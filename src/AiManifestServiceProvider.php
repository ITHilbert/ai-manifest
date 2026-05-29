<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ITHilbert\AiManifest\Commands\AiManifestGenerateCommand;
use ITHilbert\AiManifest\Http\AiManifestController;
use ITHilbert\AiManifest\Macros\RouteAiManifestMacro;
use ITHilbert\AiManifest\Services\AiManifestService;

class AiManifestServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RouteAiManifestMacro::register();

        $middleware = (array) config('ai-manifest.middleware', []);

        $route = Route::get('/.well-known/ai-manifest.json', AiManifestController::class)
            ->name('ai-manifest');

        if (! empty($middleware)) {
            $route->middleware($middleware);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/Config/ai-manifest.php' => config_path('ai-manifest.php'),
            ], 'ai-manifest-config');

            $this->commands([
                AiManifestGenerateCommand::class,
            ]);
        }
    }

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/ai-manifest.php', 'ai-manifest');

        $this->app->singleton(AiManifestService::class);
        $this->app->alias(AiManifestService::class, 'AiManifest');
    }
}

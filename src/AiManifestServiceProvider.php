<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ITHilbert\AiManifest\Ard\ArdFormat;
use ITHilbert\AiManifest\Commands\AiManifestGenerateCommand;
use ITHilbert\AiManifest\Http\ArdManifestController;
use ITHilbert\AiManifest\Http\LlmsTxtController;
use ITHilbert\AiManifest\Macros\RouteAiManifestMacro;
use ITHilbert\AiManifest\Services\AiManifestService;

class AiManifestServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RouteAiManifestMacro::register();

        $this->loadViewsFrom(__DIR__.'/Resources/views', 'ai-manifest');

        if (! $this->app->routesAreCached()) {
            $this->registerRoutes();
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

    /**
     * Alle Routen des Pakets tragen die Middleware aus der Config.
     */
    private function registerRoutes(): void
    {
        Route::middleware((array) config('ai-manifest.middleware', []))->group(function (): void {
            Route::get(ArdFormat::PFAD_ARD, ArdManifestController::class)->name('ai-manifest.ard');

            if ((bool) config('ai-manifest.ai_catalog_legacy', true)) {
                Route::get(ArdFormat::PFAD_AI_CATALOG, ArdManifestController::class)->name('ai-manifest.ai-catalog');
            }

            Route::get('/llms.txt', LlmsTxtController::class)->name('ai-manifest.llms-txt');
        });
    }
}

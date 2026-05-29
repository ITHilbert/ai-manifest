<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Macros;

use Illuminate\Routing\Route;
use ITHilbert\AiManifest\Builders\AiManifestRouteBuilder;

final class RouteAiManifestMacro
{
    /**
     * Registriert das Route-Macro "aiManifest".
     *
     * Verwendung:
     *   ->aiManifest()                                             // Standardwerte
     *   ->aiManifest(['summary' => '...', 'auth' => true])        // Array-Syntax
     *   ->aiManifest()->summary('...')->auth()->param('x','string') // Fluente Syntax
     */
    public static function register(): void
    {
        Route::macro('aiManifest', function (array $options = []) {
            /** @var Route $this */
            $action = $this->getAction();
            $existing = $action['ai_manifest_meta'] ?? [];

            $action['ai_manifest_meta'] = array_merge($existing, $options);
            $this->setAction($action);

            return new AiManifestRouteBuilder($this);
        });
    }
}

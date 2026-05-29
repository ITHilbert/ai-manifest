<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | App-Name im Manifest
    |--------------------------------------------------------------------------
    | null → fällt auf config('app.name') zurück.
    */
    'name' => null,

    /*
    |--------------------------------------------------------------------------
    | Beschreibung
    |--------------------------------------------------------------------------
    */
    'description' => '',

    /*
    |--------------------------------------------------------------------------
    | Manifest-Version
    |--------------------------------------------------------------------------
    */
    'version' => '1.0',

    /*
    |--------------------------------------------------------------------------
    | Auth-Konfiguration
    |--------------------------------------------------------------------------
    | type: none | bearer | basic | oauth2
    */
    'auth' => [
        'type' => 'bearer',
        'token_endpoint' => '/api/token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mechanismen
    |--------------------------------------------------------------------------
    | Manuell gepflegte Liste der angebotenen Mechanismen.
    | Beispiel:
    |   ['type' => 'http_api', 'base' => '/api', 'schema' => '/.well-known/openapi.json'],
    |   ['type' => 'mcp', 'url' => 'https://example.com/mcp'],
    */
    'mechanisms' => [],

    /*
    |--------------------------------------------------------------------------
    | Rate-Limits
    |--------------------------------------------------------------------------
    | null = keine Rate-Limit-Angabe im Manifest
    | Beispiel: ['requests_per_minute' => 60]
    */
    'rate_limits' => null,

    /*
    |--------------------------------------------------------------------------
    | Kontakt
    |--------------------------------------------------------------------------
    | null = kein Kontakt-Feld im Manifest
    | Beispiel: ['email' => 'api@example.com', 'url' => 'https://example.com/support']
    */
    'contact' => null,

    /*
    |--------------------------------------------------------------------------
    | Öffentliche Verfügbarkeit
    |--------------------------------------------------------------------------
    | true  = Manifest ohne Auth erreichbar (Cold-Discovery für unbekannte Agenten)
    | false = Route wird trotzdem registriert; Zugriff über middleware steuern
    */
    'public' => true,

    /*
    |--------------------------------------------------------------------------
    | Middleware für die Manifest-Route
    |--------------------------------------------------------------------------
    | Wird immer angewandt. Beispiel: ['auth:sanctum']
    */
    'middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (Sekunden)
    |--------------------------------------------------------------------------
    | 0 = kein Caching, immer live generieren
    */
    'cache_ttl' => 3600,

];

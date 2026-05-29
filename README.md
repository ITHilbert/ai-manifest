# ithilbert/ai-manifest

Laravel-Package für AI-Agent-Discovery nach RFC 8615.

Liefert unter `/.well-known/ai-manifest.json` eine maschinenlesbare Beschreibung aller Capabilities einer Laravel-App — direkt aus den Route-Definitionen, analog zu `ithilbert/sitemap`.

## Installation

Der ServiceProvider und die `AiManifest`-Facade werden über Laravel Package Auto-Discovery
(`extra.laravel` in der `composer.json`) automatisch registriert — kein manueller Eintrag nötig.

> **Workspace-Hinweis (monorepo `packages_dev`):** Da die Packages hier nicht via Packagist,
> sondern per PSR-4-Path eingebunden sind, muss der Autoload in der **root-`composer.json`**
> stehen und es ist nicht das Auto-Discovery aktiv:
> ```json
> "autoload":     { "psr-4": { "ITHilbert\\AiManifest\\":        "packages/ai-manifest/src/" } },
> "autoload-dev": { "psr-4": { "ITHilbert\\AiManifest\\Tests\\": "packages/ai-manifest/tests/" } }
> ```
> Danach `composer dump-autoload`. Der Provider wird in diesem Setup manuell in `config/app.php`
> eingetragen (so wie die anderen ITHilbert-Packages im Workspace).

Config publishen (optional):
```bash
php artisan vendor:publish --tag=ai-manifest-config
```

## Routen annotieren

```php
// routes/api.php

Route::get('/api/domains', [DomainController::class, 'index'])
    ->name('domains.index')
    ->aiManifest()
    ->summary('Alle Domains auflisten');

Route::post('/api/domains', [DomainController::class, 'store'])
    ->name('domains.create')
    ->aiManifest()
    ->summary('Domain erstellen')
    ->auth()                                        // auth=true
    ->param('name', 'string', true, 'Domain-Name') // name, type, required, desc
    ->param('tld', 'string', false, 'TLD');

Route::delete('/api/domains/{id}', [DomainController::class, 'destroy'])
    ->name('domains.delete')
    ->aiManifest(['summary' => 'Domain löschen', 'auth' => true]);

// Mechanismus-Override (wenn diese Route von einem MCP-Server bedient wird)
Route::post('/mcp/tools/call', [McpController::class, 'call'])
    ->name('mcp.call')
    ->aiManifest()
    ->summary('MCP Tool Call')
    ->mechanism('mcp');
```

Alle Fluent-Setter:

| Methode | Beschreibung |
|---|---|
| `->summary(string)` | Kurzbeschreibung der Capability |
| `->auth(bool = true)` | Erfordert Authentifizierung |
| `->param(name, type, required, desc)` | Parameter hinzufügen |
| `->mechanism(string\|Mechanism)` | Mechanismus-Override |
| `->id(string)` | Explizite Capability-ID (Default: Route-Name) |
| `->()` | `__invoke()` gibt die Route zurück (Chaining) |

## Dynamische Capabilities (Generator-Callback)

```php
// AppServiceProvider::boot()
AiManifest::addGenerator(function () {
    return DB::table('api_endpoints')->get()->map(fn ($e) => [
        'id'      => $e->slug,
        'path'    => $e->path,
        'method'  => $e->method,
        'summary' => $e->description,
        'auth'    => (bool) $e->requires_auth,
    ])->toArray();
});
```

Pflicht-Keys in Generator-Einträgen: `id` und `path`. Einträge ohne diese Keys werden verworfen.

## Config (`config/ai-manifest.php`)

```php
return [
    'name'        => null,              // null → app.name
    'description' => '',
    'version'     => '1.0',
    'auth'        => [
        'type'           => 'bearer',
        'token_endpoint' => '/api/token',
    ],
    'mechanisms'  => [
        // ['type' => 'http_api', 'base' => '/api', 'schema' => '/.well-known/openapi.json'],
        // ['type' => 'mcp', 'url' => 'https://example.com/mcp'],
    ],
    'rate_limits' => null,
    'contact'     => null,
    'public'      => true,              // false → kein öffentlicher Zugriff
    'middleware'  => [],                // z.B. ['auth:sanctum']
    'cache_ttl'   => 3600,             // 0 = kein Cache
];
```

## Sicherheit & Exposure

Das Manifest ist eine **Landkarte der maschinell bedienbaren Oberfläche** deiner App. Zwei Schutzebenen:

1. **Opt-in:** Nur Routen, die explizit mit `->aiManifest()` markiert sind, erscheinen. Interne/Admin-Routen
   landen niemals versehentlich im Manifest.
2. **Zugriffssteuerung** über die Config:
   - `'public' => true` (Default): `/.well-known/ai-manifest.json` ist ohne Auth abrufbar — echtes
     „Cold Discovery" durch unbekannte Agenten.
   - `'public' => false` + `'middleware' => ['auth:sanctum']`: Nur authentifizierte Agenten sehen das Manifest.

> Lege keine Capability ins Manifest, deren bloße Existenz schon ein Information-Leak wäre, solange
> `public = true` ist. Im Zweifel `public = false` + passende Middleware.

## Statisches File exportieren

```bash
php artisan ai-manifest:generate
# Default: public/.well-known/ai-manifest.json

php artisan ai-manifest:generate --path=/custom/path/manifest.json
```

## Beispiel-Output

```json
{
    "name": "MeineApp",
    "version": "1.0",
    "auth": {
        "type": "bearer",
        "token_endpoint": "/api/token"
    },
    "capabilities": [
        {
            "id": "domains.index",
            "method": "GET",
            "path": "/api/domains",
            "summary": "Alle Domains auflisten"
        },
        {
            "id": "domains.create",
            "method": "POST",
            "path": "/api/domains",
            "summary": "Domain erstellen",
            "auth": true,
            "params": {
                "name": {
                    "type": "string",
                    "required": true,
                    "desc": "Domain-Name"
                },
                "tld": {
                    "type": "string",
                    "required": false,
                    "desc": "TLD"
                }
            }
        }
    ]
}
```

## Mechanismen-Enum

`ITHilbert\AiManifest\Enums\Mechanism`:

- `HttpApi` = `'http_api'`
- `Mcp` = `'mcp'`
- `ArtisanCommand` = `'artisan_command'`

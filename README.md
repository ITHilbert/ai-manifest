# ithilbert/ai-manifest

Laravel-Package für die Auffindbarkeit einer Website durch KI-Agenten:

- **ARD-Manifest** (Agent Resource Discovery, Entwurf v0.91) unter `/.well-known/ard.json` — Routen deklarieren sich direkt am Endpunkt per `->aiManifest()`.
- **`/llms.txt`** (llmstxt.org) aus einer Datei, einem String oder erzeugt aus der Config.
- **`<x-ai-manifest::ard-link />`** für den Verweis `rel="ard"` im `<head>`.

Lighthouse prüft beides in der Kategorie „Agentic Browsing“ (Audits `ard-schema` und `llms-txt`).
Warum ARD statt des früheren eigenen Formats: Entscheidung [0001](.project/doku/decisions/0001-ard-statt-eigenem-format.md).
Änderungen je Version stehen in [CHANGELOG.md](CHANGELOG.md).

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

## Routen des Pakets

| Pfad | Routenname | Content-Type |
|---|---|---|
| `/.well-known/ard.json` | `ai-manifest.ard` | `application/ai-catalog+json; charset=utf-8` |
| `/llms.txt` | `ai-manifest.llms-txt` | `text/plain; charset=utf-8` |

Alle drei tragen die Middleware aus `middleware` in der Config.

## ARD-Manifest: `/.well-known/ard.json`

Der Body ist ein JSON-Objekt mit der Liste `entries`. Sie besteht aus

1. einem Eintrag je Route mit `->aiManifest()`,
2. einem Eintrag je Generator-Eintrag (`AiManifest::addGenerator`),
3. den manuellen Einträgen aus `entries` in der Config.

### Firmen-Website ohne API: leere Liste

Eine Firmen-Website ohne API markiert keine Routen und trägt nichts ein. Dann liefert die Route die leere Liste
`{"entries":[]}` — gültig und ohne Warnung im Konformitätstest. Ein erfundener Eintrag wäre schlechter
(z. B. `text/markdown` für die llms.txt ist kein Standard-Medientyp und würde warnen). Den Weg zur llms.txt
zeigt stattdessen der Verweis im `<head>` bzw. die Datei selbst.

### Eintragsform einer Route

```php
// routes/api.php

Route::post('/api/domains', [DomainController::class, 'store'])
    ->name('domains.create')
    ->aiManifest()
    ->summary('Domain erstellen')
    ->auth()                                        // auth=true
    ->param('name', 'string', true, 'Domain-Name') // name, type, required, desc
    ->mechanism('http_api')
    ->queries(['Lege die Domain beispiel.de an', 'Neue Domain registrieren']);
```

ergibt (mit `publisher` = `shop.example`):

```json
{
    "entries": [
        {
            "identifier": "urn:air:shop.example:routes:domains.create",
            "displayName": "Domain erstellen",
            "type": "application/vnd.ithilbert.ai-manifest-capability+json",
            "data": {
                "id": "domains.create",
                "method": "POST",
                "path": "/api/domains",
                "summary": "Domain erstellen",
                "auth": true,
                "params": {
                    "name": { "type": "string", "required": true, "desc": "Domain-Name" }
                },
                "mechanism": "http_api"
            },
            "representativeQueries": [
                "Lege die Domain beispiel.de an",
                "Neue Domain registrieren"
            ]
        }
    ]
}
```

Regeln:

- **`identifier`** = `urn:air:<publisher>:<namespace>:<id>`. `<id>` ist die Capability-ID: `->id()`, sonst der Routenname,
  sonst `"<methode>:/<pfad>"` (z. B. `get:/api/foo`). Zeichen außerhalb `[A-Za-z0-9._-]` werden je durch `-` ersetzt
  (`get:/api/foo` → `get--api-foo`). Gleiche identifier bekommen `-2`, `-3` … angehängt.
- **`displayName`** = `summary`, ohne Summary die ID.
- **`type`** = `application/vnd.ithilbert.ai-manifest-capability+json` (Erweiterungstyp; der Konformitätstest meldet ihn nur als Hinweis).
- **`data`** = die Capability: `id`, `method`, `path`, `summary`; `auth` nur wenn wahr, `params` nur wenn vorhanden, `mechanism` nur wenn gesetzt. Kein `url`.
- **`representativeQueries`**: die per `->queries([...])` angegebenen Beispielfragen — leere und doppelte fallen weg, höchstens fünf.
  Bleiben weniger als zwei, gelten ersatzweise `displayName` und `"<METHOD> <path>"`.

Alle Fluent-Setter:

| Methode | Beschreibung |
|---|---|
| `->summary(string)` | Kurzbeschreibung, wird `displayName` |
| `->auth(bool = true)` | Erfordert Authentifizierung |
| `->param(name, type, required, desc)` | Parameter hinzufügen |
| `->mechanism(string\|Mechanism)` | Mechanismus (`http_api`, `mcp`, `artisan_command`) |
| `->id(string)` | Explizite Capability-ID (Default: Routenname) |
| `->queries(list<string>)` | Beispielfragen für `representativeQueries` (2 bis 5) |
| `->()` | `__invoke()` gibt die Route zurück (Chaining) |

Array-Syntax geht ebenso: `->aiManifest(['summary' => 'Domain löschen', 'auth' => true, 'queries' => ['…', '…']])`.

### Dynamische Einträge (Generator-Callback)

```php
// AppServiceProvider::boot()
AiManifest::addGenerator(function () {
    return DB::table('api_endpoints')->get()->map(fn ($e) => [
        'id'      => $e->slug,
        'path'    => $e->path,
        'method'  => $e->method,
        'summary' => $e->description,
        'auth'    => (bool) $e->requires_auth,
        'queries' => [$e->beispiel_1, $e->beispiel_2],
    ])->toArray();
});
```

Pflicht-Keys in Generator-Einträgen: `id` und `path`. Einträge ohne diese Keys werden verworfen.
Der Schlüssel `queries` entspricht `->queries()` an der Route.

### Manuelle Einträge (`entries`)

```php
'entries' => [
    [
        'name'        => 'preise',                       // wird urn:air:<publisher>:site:preise
        'displayName' => 'Preisliste',
        'type'        => 'application/json',
        'url'         => 'https://shop.example/preise.json',
        'representativeQueries' => ['Was kostet der Tarif S?', 'Gibt es Rabatte?'],
        'description' => 'Aktuelle Preise aller Tarife',
    ],
],
```

Pflicht: `name`, `displayName`, `type` und genau eines von `url`/`data`. Weitere Schlüssel des Schemas
(`representativeQueries`, `description`, `tags` …) gehen unverändert durch. Ein Eintrag mit fehlender Pflichtangabe
oder mit `url` und `data` zugleich wird ohne Fehler verworfen.

## Link-Komponente

Im `<head>` des Layouts:

```blade
<x-ai-manifest::ard-link />
```

ergibt `<link rel="ard" href="https://ihre-domain.de/.well-known/ard.json">` (absolute URL der App, `url()`).

## llms.txt

`GET /llms.txt` liefert den Inhalt nach diesem Vorrang aus `llms_txt` in der Config:

1. `datei` — absoluter Pfad zu einer Markdown-Datei, z. B. `resource_path('llms.md')`,
2. sonst `inhalt` — der Text als String,
3. sonst erzeugt aus `name`/`description` und `links`:

```php
'name'        => 'Muster GmbH',
'description' => 'Webentwicklung und Hosting in Musterstadt',
'llms_txt'    => [
    'datei'  => null,
    'inhalt' => null,
    'links'  => [
        ['title' => 'Leistungen', 'url' => 'https://muster.de/leistungen', 'desc' => 'Was wir anbieten'],
        ['title' => 'Kontakt', 'url' => 'https://muster.de/kontakt'],
    ],
],
```

ergibt

```markdown
# Muster GmbH

> Webentwicklung und Hosting in Musterstadt

## Links

- [Leistungen](https://muster.de/leistungen): Was wir anbieten
- [Kontakt](https://muster.de/kontakt)
```

Ist nichts davon gesetzt (kein `datei`, kein `inhalt`, keine `links`) oder ist die Datei nicht lesbar, antwortet `/llms.txt` mit 404
(Lighthouse wertet das Audit dann als „nicht anwendbar“). Lighthouse verlangt eine H1, mindestens 50 Zeichen und mindestens einen Link.

## Statische Dateien

Liegt `public/llms.txt` oder `public/.well-known/ard.json` als statische Datei im Projekt, liefert der Webserver sie vor Laravel aus —
die Routen des Pakets kommen dann nicht zum Zug. Das Paket ändert daran nichts. Wer die Routen nutzen will, legt keine solche Datei an.

## Config (`config/ai-manifest.php`)

| Schlüssel | Standard | Bedeutung |
|---|---|---|
| `name` | `null` | Name für die erzeugte llms.txt; `null` → `app.name` |
| `description` | `''` | Zitatzeile der erzeugten llms.txt (leer → keine) |
| `publisher` | `null` | Publisher im identifier; `null` → Host aus `app.url` (klein, ohne Port), ohne Host `localhost` |
| `namespace` | `'routes'` | Namespace der Einträge aus Routen und Generatoren |
| `entries_namespace` | `'site'` | Namespace der manuellen Einträge |
| `entries` | `[]` | Manuelle ARD-Einträge (siehe oben) |
| `llms_txt` | `['datei' => null, 'inhalt' => null, 'links' => []]` | Quelle der llms.txt |
| `middleware` | `[]` | Middleware für alle Routen des Pakets, z. B. `['throttle:60,1']` |

Der ARD-Body wird bei jedem Abruf frisch gebaut, es gibt keinen Cache: Eine geänderte Route oder Config erscheint sofort.

## Sicherheit & Exposure

Das Manifest ist eine **Landkarte der maschinell bedienbaren Oberfläche** deiner App:

1. **Opt-in:** Nur Routen, die explizit mit `->aiManifest()` markiert sind, erscheinen. Interne/Admin-Routen
   landen niemals versehentlich im Manifest.
2. **Zugriffssteuerung** über `middleware`: Standard ist öffentlich (Agenten finden die Seite ohne Anmeldung).
   Mit z. B. `['auth:sanctum']` sehen nur angemeldete Agenten Manifest, Übergangspfad und llms.txt.

> Lege keine Capability ins Manifest, deren bloße Existenz schon ein Information-Leak wäre, solange die Routen öffentlich sind.

## Statisches File exportieren

```bash
php artisan ai-manifest:generate
# Default: public/.well-known/ard.json

php artisan ai-manifest:generate --path=/custom/path/ard.json
```

Schreibt den Body von `ard.json` und meldet die Zahl der Einträge. Achtung: Die Datei in `public/` liefert danach der Webserver
statt der Route aus (siehe „Statische Dateien“).

## Umstieg von 1.x auf 2.0

Version 2.0 ersetzt das eigene Format durch ARD. Was entfällt:

- Die Route `/.well-known/ai-manifest.json` (Name `ai-manifest`) entfällt — sie antwortet 404. Neu ist `/.well-known/ard.json`.
- `ai-manifest:generate` schreibt `ard.json` statt `ai-manifest.json`; eine alte `public/.well-known/ai-manifest.json` bitte löschen.
- Config-Schlüssel `version`, `auth`, `mechanisms`, `rate_limits`, `contact`, `public` entfallen. Zugriff steuert nur noch `middleware`.
- `AiManifestService::toArray()`, `toJson()`, `buildHead()` (und die gleichnamigen Facade-Methoden) entfallen.
  Den Body baut `ITHilbert\AiManifest\Ard\ArdManifest::body()`.
- Mit 2.1 entfällt außerdem der kurzzeitige Übergangspfad `/.well-known/ai-catalog.json` samt Route `ai-manifest.ai-catalog` und Config `ai_catalog_legacy` (env `AI_MANIFEST_LEGACY_AI_CATALOG`); er antwortet 404, maßgeblich ist `/.well-known/ard.json`.

Bleibt: `->aiManifest()` mit allen Settern, `AiManifest::addGenerator`/`clearGenerators`/`getCapabilities`, `name`, `description`,
`middleware` und der Enum `Mechanism` (Feld `mechanism` in `data`). Neu: `->queries()`, `publisher`, `namespace`,
`entries_namespace`, `entries`, `llms_txt`, die Link-Komponente und `/llms.txt`.

Hintergrund: Entscheidung [0001 — ARD statt eigenem Format](.project/doku/decisions/0001-ard-statt-eigenem-format.md).

## Mechanismen-Enum

`ITHilbert\AiManifest\Enums\Mechanism`:

- `HttpApi` = `'http_api'`
- `Mcp` = `'mcp'`
- `ArtisanCommand` = `'artisan_command'`

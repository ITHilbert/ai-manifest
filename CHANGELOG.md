# Changelog

Alle wichtigen Änderungen an diesem Paket. Format: [Keep a Changelog](https://keepachangelog.com/de/1.1.0/), Versionierung: [Semantic Versioning](https://semver.org/lang/de/).

## Unveröffentlicht (2.0.0, Breaking)

### Neu

- ARD-Manifest unter `GET /.well-known/ard.json` (Routenname `ai-manifest.ard`, Content-Type `application/ai-catalog+json; charset=utf-8`). Es ersetzt das eigene Format.
- `->aiManifest()` an einer Route erzeugt einen ARD-Eintrag: `identifier` `urn:air:<publisher>:<namespace>:<id>`, Typ `application/vnd.ithilbert.ai-manifest-capability+json`, die Capability in `data`.
- Fluent-Setter `->queries([...])` für `representativeQueries` (2 bis 5 Beispielfragen). Im Generator heißt der Schlüssel `queries`.
- Manuelle ARD-Einträge über die Config `entries`, Namespace über `entries_namespace`.
- `GET /llms.txt` (`text/plain; charset=utf-8`), Quelle in dieser Reihenfolge: Datei (`llms_txt.datei`), Text (`llms_txt.inhalt`), erzeugt aus `name`, `description` und `links`.
- Link-Komponente `<x-ai-manifest::ard-link />` für `<link rel="ard">`.
- Übergangspfad `GET /.well-known/ai-catalog.json` mit demselben Inhalt. Abschalten mit `AI_MANIFEST_LEGACY_AI_CATALOG=false`.
- Config-Schlüssel `publisher`, `namespace`, `entries_namespace`, `entries`, `ai_catalog_legacy`, `llms_txt`.

### Geändert

- `php artisan ai-manifest:generate` schreibt `public/.well-known/ard.json` statt `ai-manifest.json`. Die Option `--path` bleibt. Der Befehl meldet die Zahl der Einträge.
- Der ARD-Body wird bei jedem Abruf frisch gebaut, ohne Cache. Eine geänderte Route oder Config erscheint sofort.
- `middleware` gilt für alle Routen des Pakets: ARD, Übergangspfad und llms.txt.

### Entfernt (Breaking)

- Route `GET /.well-known/ai-manifest.json` (Name `ai-manifest`). Sie antwortet mit 404.
- Config-Schlüssel `cache_ttl` und `ArdManifest::fingerabdruck()`: Der ARD-Cache entfällt. Wer `cache_ttl` in der eigenen Config setzt, kann den Eintrag löschen.
- Config-Schlüssel `version`, `auth`, `mechanisms`, `rate_limits`, `contact`, `public`.
- `AiManifestService::toArray()`, `toJson()` und `buildHead()` samt den gleichnamigen Facade-Methoden.

Bestehen bleiben `->aiManifest()` mit allen Settern, `AiManifest::addGenerator`, `clearGenerators` und `getCapabilities`, die Config `name`, `description` und `middleware` sowie der Enum `Mechanism`.

#### Umstieg von 1.x auf 2.0

1. Verweise und Abfragen von `/.well-known/ai-manifest.json` auf `/.well-known/ard.json` umstellen.
2. Eine statisch exportierte `public/.well-known/ai-manifest.json` löschen und mit `php artisan ai-manifest:generate` die `ard.json` neu schreiben.
3. Die entfallenen Config-Schlüssel entfernen. Zugriffsschutz steuert nur noch `middleware`.
4. Code, der `toArray()`, `toJson()` oder `buildHead()` nutzt, auf `ITHilbert\AiManifest\Ard\ArdManifest::body()` umstellen.

Hintergrund: [Entscheidung 0001](.project/doku/decisions/0001-ard-statt-eigenem-format.md).

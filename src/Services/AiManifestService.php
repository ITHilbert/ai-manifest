<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Services;

use Illuminate\Support\Facades\Route;
use ITHilbert\AiManifest\Data\CapabilityEntry;
use ITHilbert\AiManifest\Data\CapabilityParam;
use ITHilbert\AiManifest\Enums\Mechanism;

final class AiManifestService
{
    /**
     * @var array<callable>
     */
    private array $generators = [];

    public function addGenerator(callable $generator): void
    {
        $this->generators[] = $generator;
    }

    public function clearGenerators(): void
    {
        $this->generators = [];
    }

    /**
     * Liefert alle Capabilities aus Routen und Generatoren.
     *
     * @return list<CapabilityEntry>
     */
    public function getCapabilities(): array
    {
        $capabilities = [];

        $this->collectRouteCapabilities($capabilities);
        $this->collectGeneratorCapabilities($capabilities);

        return $capabilities;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildHead(): array
    {
        $head = [];

        $head['name'] = config('ai-manifest.name') ?? config('app.name', 'Laravel');

        $description = config('ai-manifest.description', '');
        if ($description !== '' && $description !== null) {
            $head['description'] = (string) $description;
        }

        $version = config('ai-manifest.version', '1.0');
        if ($version !== null) {
            $head['version'] = (string) $version;
        }

        $auth = config('ai-manifest.auth');
        if (! empty($auth)) {
            $head['auth'] = $auth;
        }

        $mechanisms = config('ai-manifest.mechanisms', []);
        if (! empty($mechanisms)) {
            $head['mechanisms'] = $mechanisms;
        }

        $rateLimits = config('ai-manifest.rate_limits');
        if ($rateLimits !== null) {
            $head['rate_limits'] = $rateLimits;
        }

        $contact = config('ai-manifest.contact');
        if ($contact !== null) {
            $head['contact'] = $contact;
        }

        return $head;
    }

    /**
     * Kompletter Manifest-Body als Array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $manifest = $this->buildHead();
        $manifest['capabilities'] = array_map(
            static fn (CapabilityEntry $entry) => $entry->toArray(),
            $this->getCapabilities()
        );

        return $manifest;
    }

    /**
     * Liefert einen stabilen MD5-Fingerprint über das gesamte Manifest (Kopf + Capabilities).
     *
     * Gleiche Routes und gleiche Kopf-Konfiguration → gleicher Hash.
     * Ändert sich eine Route, die Konfiguration oder kommt eine neue Route hinzu → anderer Hash.
     * Capabilities werden vor dem Hashen nach id/path sortiert, damit die Reihenfolge
     * der Routen-Registrierung das Ergebnis nicht beeinflusst.
     */
    public function manifestFingerprint(): string
    {
        $manifest = $this->buildHead();

        $rows = [];
        foreach ($this->getCapabilities() as $entry) {
            $rows[] = $entry->toArray();
        }

        usort($rows, static function (array $a, array $b): int {
            $idCmp = strcmp((string) ($a['id'] ?? ''), (string) ($b['id'] ?? ''));
            if ($idCmp !== 0) {
                return $idCmp;
            }

            return strcmp((string) ($a['path'] ?? ''), (string) ($b['path'] ?? ''));
        });

        $manifest['capabilities'] = $rows;

        return md5((string) json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Kompletter Manifest-Body als formatierter JSON-String.
     */
    public function toJson(): string
    {
        return (string) json_encode(
            $this->toArray(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * @param  list<CapabilityEntry>  $capabilities
     */
    private function collectRouteCapabilities(array &$capabilities): void
    {
        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction();

            if (! isset($action['ai_manifest_meta'])) {
                continue;
            }

            $meta = $action['ai_manifest_meta'];

            $id = $meta['id']
                ?? ($route->getName() !== null && $route->getName() !== '' ? $route->getName() : null)
                ?? $this->buildDefaultId($route);

            $method = $this->resolveMethod($route);
            $path = '/'.ltrim($route->uri(), '/');

            $params = $this->buildParams($meta['params'] ?? []);

            $mechanism = null;
            if (isset($meta['mechanism'])) {
                $mechanism = Mechanism::fromInput((string) $meta['mechanism']);
            }

            $capabilities[] = new CapabilityEntry(
                id: $id,
                method: $method,
                path: $path,
                summary: (string) ($meta['summary'] ?? ''),
                auth: (bool) ($meta['auth'] ?? false),
                params: $params,
                mechanism: $mechanism,
            );
        }
    }

    /**
     * @param  list<CapabilityEntry>  $capabilities
     */
    private function collectGeneratorCapabilities(array &$capabilities): void
    {
        foreach ($this->generators as $generator) {
            $entries = $generator();

            if (! is_iterable($entries)) {
                continue;
            }

            foreach ($entries as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                if (! isset($entry['id']) || ! isset($entry['path'])) {
                    continue;
                }

                $method = strtoupper((string) ($entry['method'] ?? 'GET'));
                $params = $this->buildParams($entry['params'] ?? []);
                $mechanism = null;

                if (isset($entry['mechanism'])) {
                    $mechanism = Mechanism::fromInput((string) $entry['mechanism']);
                }

                $capabilities[] = new CapabilityEntry(
                    id: (string) $entry['id'],
                    method: $method,
                    path: (string) $entry['path'],
                    summary: (string) ($entry['summary'] ?? ''),
                    auth: (bool) ($entry['auth'] ?? false),
                    params: $params,
                    mechanism: $mechanism,
                );
            }
        }
    }

    /**
     * Ermittelt die erste relevante HTTP-Methode einer Route (HEAD/OPTIONS ausgeschlossen).
     */
    private function resolveMethod(\Illuminate\Routing\Route $route): string
    {
        foreach ($route->methods() as $method) {
            if (! in_array($method, ['HEAD', 'OPTIONS'], true)) {
                return strtoupper($method);
            }
        }

        return strtoupper($route->methods()[0]);
    }

    /**
     * Baut einen Default-Identifier aus method+path.
     */
    private function buildDefaultId(\Illuminate\Routing\Route $route): string
    {
        $method = strtolower($this->resolveMethod($route));
        $path = trim($route->uri(), '/');

        return $method.':/'.$path;
    }

    /**
     * Baut eine Liste von CapabilityParam-Objekten aus rohen Meta-Arrays.
     *
     * @param  list<array{name: string, type: string, required?: bool, desc?: string|null}>  $rawParams
     * @return list<CapabilityParam>
     */
    private function buildParams(array $rawParams): array
    {
        $params = [];

        foreach ($rawParams as $raw) {
            if (! is_array($raw) || ! isset($raw['name'], $raw['type'])) {
                continue;
            }

            $params[] = new CapabilityParam(
                name: (string) $raw['name'],
                type: (string) $raw['type'],
                required: (bool) ($raw['required'] ?? false),
                desc: isset($raw['desc']) ? (string) $raw['desc'] : null,
            );
        }

        return $params;
    }
}

<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Services;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use ITHilbert\AiManifest\Data\CapabilityEntry;
use ITHilbert\AiManifest\Data\CapabilityParam;
use ITHilbert\AiManifest\Enums\Mechanism;

/**
 * Sammelt die Capabilities aus markierten Routen (`->aiManifest()`) und Generatoren.
 * Das Ausgabeformat (ARD) baut ITHilbert\AiManifest\Ard\ArdManifest.
 */
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
     * @param  list<CapabilityEntry>  $capabilities
     */
    private function collectRouteCapabilities(array &$capabilities): void
    {
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $action = $route->getAction();

            if (! isset($action['ai_manifest_meta'])) {
                continue;
            }

            $meta = $action['ai_manifest_meta'];

            $id = $meta['id']
                ?? ($route->getName() !== null && $route->getName() !== '' ? $route->getName() : null)
                ?? $this->buildDefaultId($route);

            $mechanism = null;
            if (isset($meta['mechanism'])) {
                $mechanism = Mechanism::fromInput((string) $meta['mechanism']);
            }

            $capabilities[] = new CapabilityEntry(
                id: (string) $id,
                method: $this->resolveMethod($route),
                path: '/'.ltrim($route->uri(), '/'),
                summary: (string) ($meta['summary'] ?? ''),
                auth: (bool) ($meta['auth'] ?? false),
                params: $this->buildParams($meta['params'] ?? []),
                mechanism: $mechanism,
                queries: $this->buildQueries($meta['queries'] ?? []),
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

                $mechanism = null;
                if (isset($entry['mechanism'])) {
                    $mechanism = Mechanism::fromInput((string) $entry['mechanism']);
                }

                $capabilities[] = new CapabilityEntry(
                    id: (string) $entry['id'],
                    method: strtoupper((string) ($entry['method'] ?? 'GET')),
                    path: (string) $entry['path'],
                    summary: (string) ($entry['summary'] ?? ''),
                    auth: (bool) ($entry['auth'] ?? false),
                    params: $this->buildParams($entry['params'] ?? []),
                    mechanism: $mechanism,
                    queries: $this->buildQueries($entry['queries'] ?? []),
                );
            }
        }
    }

    /**
     * Ermittelt die erste relevante HTTP-Methode einer Route (HEAD/OPTIONS ausgeschlossen).
     */
    private function resolveMethod(RoutingRoute $route): string
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
    private function buildDefaultId(RoutingRoute $route): string
    {
        $method = strtolower($this->resolveMethod($route));
        $path = trim($route->uri(), '/');

        return $method.':/'.$path;
    }

    /**
     * Baut eine Liste von CapabilityParam-Objekten aus rohen Meta-Arrays.
     *
     * @param  array<mixed>  $rawParams  ungeprüfte Angaben aus Route-Meta oder Generator
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

    /**
     * Nur Zeichenketten; Auswahl (leer, Dubletten, Höchstzahl) trifft der ARD-Aufbau.
     *
     * @return list<string>
     */
    private function buildQueries(mixed $rawQueries): array
    {
        if (! is_iterable($rawQueries)) {
            return [];
        }

        $queries = [];

        foreach ($rawQueries as $query) {
            if (is_string($query)) {
                $queries[] = $query;
            }
        }

        return $queries;
    }
}

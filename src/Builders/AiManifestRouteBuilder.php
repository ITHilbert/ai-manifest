<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Builders;

use Illuminate\Routing\Route;
use ITHilbert\AiManifest\Enums\Mechanism;

/**
 * Fluenter Builder für AI-Manifest-Metadaten auf einer Route.
 *
 * Verwendung:
 *   ->aiManifest()
 *   ->aiManifest(['summary' => 'Liste alle Domains', 'auth' => true])
 *   ->aiManifest()->summary('...')->auth()->param('name', 'string', true, 'Domain-Name')
 *   ->aiManifest()->summary('...')->auth()()  // __invoke() gibt Route zurück
 */
final class AiManifestRouteBuilder
{
    public function __construct(private readonly Route $route) {}

    /**
     * Kurzbeschreibung der Capability setzen.
     */
    public function summary(string $value): static
    {
        return $this->setMeta('summary', $value);
    }

    /**
     * Auth-Pflicht setzen.
     */
    public function auth(bool $required = true): static
    {
        return $this->setMeta('auth', $required);
    }

    /**
     * Einen Parameter an meta['params'][] anhängen.
     */
    public function param(string $name, string $type, bool $required = false, ?string $desc = null): static
    {
        $action = $this->route->getAction();
        $existing = $action['ai_manifest_meta']['params'] ?? [];

        $existing[] = [
            'name' => $name,
            'type' => $type,
            'required' => $required,
            'desc' => $desc,
        ];

        $action['ai_manifest_meta']['params'] = $existing;
        $this->route->setAction($action);

        return $this;
    }

    /**
     * Mechanismus-Override setzen. Normalisiert via Mechanism::fromInput.
     */
    public function mechanism(string|Mechanism $value): static
    {
        $enum = $value instanceof Mechanism ? $value : Mechanism::fromInput($value);

        return $this->setMeta('mechanism', $enum->value);
    }

    /**
     * Explizite Capability-ID setzen (überschreibt den Default Route-Name/method+path).
     */
    public function id(string $value): static
    {
        return $this->setMeta('id', $value);
    }

    /**
     * Beispielfragen eines Agenten zu dieser Route (ARD: representativeQueries, 2 bis 5).
     *
     * @param  array<array-key, string>  $queries
     */
    public function queries(array $queries): static
    {
        return $this->setMeta('queries', array_values($queries));
    }

    /**
     * Gibt die Route zurück.
     */
    public function __invoke(): Route
    {
        return $this->route;
    }

    private function setMeta(string $key, mixed $value): static
    {
        $action = $this->route->getAction();
        $action['ai_manifest_meta'][$key] = $value;
        $this->route->setAction($action);

        return $this;
    }
}

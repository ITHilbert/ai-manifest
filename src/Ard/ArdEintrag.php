<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Ard;

use stdClass;

/**
 * Ein Eintrag der Liste `entries` im ARD-Manifest.
 *
 * Genau eines von `url`/`data` ist gesetzt; die Prüfung liegt beim Aufbau (ArdManifest).
 */
final readonly class ArdEintrag
{
    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>  $weitere  weitere Schlüssel des Schemas (representativeQueries, description, tags …), unverändert
     */
    public function __construct(
        public string $identifier,
        public string $displayName,
        public string $type,
        public ?string $url = null,
        public ?array $data = null,
        public array $weitere = [],
    ) {}

    public function mitIdentifier(string $identifier): self
    {
        return new self($identifier, $this->displayName, $this->type, $this->url, $this->data, $this->weitere);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $eintrag = [
            'identifier' => $this->identifier,
            'displayName' => $this->displayName,
            'type' => $this->type,
        ];

        if ($this->url !== null) {
            $eintrag['url'] = $this->url;
        }

        if ($this->data !== null) {
            // Leeres data bleibt im JSON ein Objekt, keine Liste
            $eintrag['data'] = $this->data === [] ? new stdClass : $this->data;
        }

        return $eintrag + $this->weitere;
    }
}

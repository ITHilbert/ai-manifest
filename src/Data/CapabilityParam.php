<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Data;

final readonly class CapabilityParam
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $required = false,
        public ?string $desc = null,
    ) {}

    /**
     * Gibt nur gesetzte Felder zurück (kein null-desc im Output).
     *
     * @return array{type: string, required: bool, desc?: string}
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'required' => $this->required,
        ];

        if ($this->desc !== null) {
            $data['desc'] = $this->desc;
        }

        return $data;
    }
}

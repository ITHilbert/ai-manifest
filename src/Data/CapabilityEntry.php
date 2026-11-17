<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Data;

use ITHilbert\AiManifest\Enums\Mechanism;

final readonly class CapabilityEntry
{
    /**
     * @param  list<CapabilityParam>  $params
     * @param  list<string>  $queries  Beispielfragen für ARD (representativeQueries), nicht Teil von toArray()
     */
    public function __construct(
        public string $id,
        public string $method,
        public string $path,
        public string $summary,
        public bool $auth = false,
        public array $params = [],
        public ?Mechanism $mechanism = null,
        public array $queries = [],
    ) {}

    /**
     * Rendert id/method/path/summary immer; auth nur wenn true;
     * params nur wenn nicht leer (als Map name=>{type,required,desc});
     * mechanism nur wenn gesetzt (->value).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'method' => $this->method,
            'path' => $this->path,
            'summary' => $this->summary,
        ];

        if ($this->auth) {
            $data['auth'] = true;
        }

        if ($this->params !== []) {
            $paramsMap = [];

            foreach ($this->params as $param) {
                $paramsMap[$param->name] = $param->toArray();
            }

            $data['params'] = $paramsMap;
        }

        if ($this->mechanism !== null) {
            $data['mechanism'] = $this->mechanism->value;
        }

        return $data;
    }
}

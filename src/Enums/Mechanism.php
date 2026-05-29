<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Enums;

enum Mechanism: string
{
    case HttpApi = 'http_api';
    case Mcp = 'mcp';
    case ArtisanCommand = 'artisan_command';

    public static function fromInput(string $value): self
    {
        $normalized = strtolower(trim($value));

        return self::tryFrom($normalized)
            ?? throw new \InvalidArgumentException(
                "Ungültiger mechanism-Wert: '{$value}'. Erlaubt: "
                .implode(', ', array_column(self::cases(), 'value'))
            );
    }
}

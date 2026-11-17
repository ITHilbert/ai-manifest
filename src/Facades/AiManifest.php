<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Facades;

use Illuminate\Support\Facades\Facade;
use ITHilbert\AiManifest\Data\CapabilityEntry;
use ITHilbert\AiManifest\Services\AiManifestService;

/**
 * @method static void addGenerator(callable $generator)
 * @method static void clearGenerators()
 * @method static list<CapabilityEntry> getCapabilities()
 *
 * Den Body von /.well-known/ard.json baut ITHilbert\AiManifest\Ard\ArdManifest.
 *
 * @see AiManifestService
 */
final class AiManifest extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return AiManifestService::class;
    }
}

<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Http;

use Illuminate\Http\Response;
use ITHilbert\AiManifest\Ard\ArdFormat;
use ITHilbert\AiManifest\Ard\ArdManifest;

/**
 * GET /.well-known/ard.json.
 */
final class ArdManifestController
{
    public function __construct(private readonly ArdManifest $manifest) {}

    public function __invoke(): Response
    {
        return response(ArdManifest::json($this->manifest->body()), 200, ['Content-Type' => ArdFormat::CONTENT_TYPE]);
    }
}

<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Http;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use ITHilbert\AiManifest\Ard\ArdFormat;
use ITHilbert\AiManifest\Ard\ArdManifest;

/**
 * GET /.well-known/ard.json (und Übergangspfad /.well-known/ai-catalog.json).
 */
final class ArdManifestController
{
    public function __construct(private readonly ArdManifest $manifest) {}

    public function __invoke(): Response
    {
        $body = $this->manifest->body();
        $ttl = (int) config('ai-manifest.cache_ttl', 3600);

        if ($ttl > 0) {
            $body = Cache::remember('ai-manifest.ard.'.ArdManifest::fingerabdruck($body), $ttl, static fn (): array => $body);
        }

        return response(ArdManifest::json($body), 200, ['Content-Type' => ArdFormat::CONTENT_TYPE]);
    }
}

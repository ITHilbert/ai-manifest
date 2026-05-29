<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use ITHilbert\AiManifest\Services\AiManifestService;

final class AiManifestController
{
    public function __construct(private readonly AiManifestService $service) {}

    public function __invoke(): JsonResponse
    {
        $ttl = (int) config('ai-manifest.cache_ttl', 3600);

        if ($ttl > 0) {
            $key = 'ai-manifest.payload.'.$this->service->manifestFingerprint();
            $payload = Cache::remember(
                $key,
                $ttl,
                fn () => $this->service->toArray()
            );
        } else {
            $payload = $this->service->toArray();
        }

        return response()->json($payload);
    }
}

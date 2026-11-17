<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Http;

use Illuminate\Http\Response;
use ITHilbert\AiManifest\LlmsTxt\LlmsTxtInhalt;

/**
 * GET /llms.txt — 404, solange nichts konfiguriert ist.
 */
final class LlmsTxtController
{
    public function __construct(private readonly LlmsTxtInhalt $llmsTxt) {}

    public function __invoke(): Response
    {
        $inhalt = $this->llmsTxt->inhalt();

        abort_if($inhalt === null, 404);

        return response($inhalt, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}

<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Concerns;

/**
 * Gemeinsamer Helfer der ARD-Tests: ruft ard.json ab (Kanal wie ein Agent).
 */
trait ArdAbruf
{
    /**
     * @return list<array<string, mixed>>
     */
    protected function ardEintraege(): array
    {
        $response = $this->get('/.well-known/ard.json');
        $response->assertOk();

        return $response->json('entries');
    }

    /**
     * Der Eintrag, dessen identifier auf ":<suffix>" endet.
     *
     * @return array<string, mixed>
     */
    protected function ardEintrag(string $suffix): array
    {
        foreach ($this->ardEintraege() as $eintrag) {
            if (str_ends_with((string) $eintrag['identifier'], ':'.$suffix)) {
                return $eintrag;
            }
        }

        $this->fail("Kein ARD-Eintrag mit identifier-Ende ':{$suffix}'");
    }
}

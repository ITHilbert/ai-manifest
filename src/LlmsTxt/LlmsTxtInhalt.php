<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\LlmsTxt;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;

/**
 * Inhalt von /llms.txt aus der Config `ai-manifest.llms_txt`.
 *
 * Vorrang: `datei` > `inhalt` > erzeugt aus name/description und `links`.
 */
final readonly class LlmsTxtInhalt
{
    public function __construct(
        private Repository $config,
        private Filesystem $filesystem,
    ) {}

    /**
     * Null, wenn nichts konfiguriert ist oder die Datei nicht lesbar ist (Route antwortet 404).
     */
    public function inhalt(): ?string
    {
        $llms = (array) $this->config->get('ai-manifest.llms_txt', []);

        $datei = $llms['datei'] ?? null;
        if (is_string($datei) && $datei !== '') {
            return $this->ausDatei($datei);
        }

        $inhalt = $llms['inhalt'] ?? null;
        if (is_string($inhalt) && trim($inhalt) !== '') {
            return $inhalt;
        }

        return $this->erzeugt(is_array($llms['links'] ?? null) ? $llms['links'] : []);
    }

    private function ausDatei(string $datei): ?string
    {
        if (! $this->filesystem->isFile($datei) || ! $this->filesystem->isReadable($datei)) {
            return null;
        }

        return $this->filesystem->get($datei);
    }

    /**
     * @param  array<mixed>  $links
     */
    private function erzeugt(array $links): ?string
    {
        $zeilen = [];

        foreach ($links as $link) {
            $zeile = is_array($link) ? $this->linkZeile($link) : null;

            if ($zeile !== null) {
                $zeilen[] = $zeile;
            }
        }

        if ($zeilen === []) {
            return null;
        }

        $name = $this->config->get('ai-manifest.name') ?? $this->config->get('app.name', 'Laravel');
        $text = '# '.$name."\n\n";

        $beschreibung = $this->config->get('ai-manifest.description');
        if (is_string($beschreibung) && trim($beschreibung) !== '') {
            $text .= '> '.$beschreibung."\n\n";
        }

        return $text."## Links\n\n".implode("\n", $zeilen)."\n";
    }

    /**
     * @param  array<mixed>  $link
     */
    private function linkZeile(array $link): ?string
    {
        $titel = $link['title'] ?? null;
        $url = $link['url'] ?? null;

        if (! is_string($titel) || $titel === '' || ! is_string($url) || $url === '') {
            return null;
        }

        $zeile = '- ['.$titel.']('.$url.')';
        $beschreibung = $link['desc'] ?? null;

        return is_string($beschreibung) && $beschreibung !== '' ? $zeile.': '.$beschreibung : $zeile;
    }
}

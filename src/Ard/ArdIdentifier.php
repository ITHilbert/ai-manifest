<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Ard;

/**
 * Baut identifier der Form `urn:air:<publisher>:<namespace>:<name>`.
 *
 * Jeder erzeugte Wert erfüllt `^urn:air:[a-zA-Z0-9.-]+(:[a-zA-Z0-9._-]+)+$`:
 * Zeichen außerhalb des erlaubten Alphabets werden je durch `-` ersetzt.
 */
final class ArdIdentifier
{
    private const string ALPHABET_PUBLISHER = 'a-zA-Z0-9.-';

    private const string ALPHABET_SEGMENT = 'A-Za-z0-9._-';

    /**
     * Publisher aus der Config, sonst Host aus der App-URL (klein, ohne Port), sonst `localhost`.
     */
    public static function publisher(mixed $konfiguriert, mixed $appUrl): string
    {
        $publisher = is_string($konfiguriert) ? trim($konfiguriert) : '';

        if ($publisher === '' && is_string($appUrl)) {
            $host = parse_url($appUrl, PHP_URL_HOST);
            $publisher = is_string($host) ? strtolower($host) : '';
        }

        $bereinigt = self::ersetzen($publisher, self::ALPHABET_PUBLISHER);

        return $bereinigt === '' ? 'localhost' : $bereinigt;
    }

    /**
     * Ein Segment (namespace oder name); leer wird zum Ersatzwert.
     */
    public static function segment(string $wert, string $ersatz = '-'): string
    {
        $bereinigt = self::ersetzen($wert, self::ALPHABET_SEGMENT);

        return $bereinigt === '' ? $ersatz : $bereinigt;
    }

    public static function urn(string $publisher, string $namespace, string $name): string
    {
        return implode(':', [ArdFormat::URN_PRAEFIX, $publisher, $namespace, self::segment($name)]);
    }

    private static function ersetzen(string $wert, string $alphabet): string
    {
        // Je Zeichen ein `-`, auch bei Mehrbyte-Zeichen; ungültiges UTF-8 je Byte
        return preg_replace('/[^'.$alphabet.']/u', '-', $wert)
            ?? (string) preg_replace('/[^'.$alphabet.']/', '-', $wert);
    }
}

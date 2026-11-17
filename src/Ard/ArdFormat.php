<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Ard;

/**
 * Feste Werte des ARD-Formats (Agent Resource Discovery, Entwurf v0.91).
 *
 * Ändert sich der Entwurf, bleibt die Änderung in src/Ard/ (Entscheidung 0001).
 */
final class ArdFormat
{
    /** Pfad des ARD-Manifests (RFC 8615). */
    public const string PFAD_ARD = '/.well-known/ard.json';

    /** Übergangspfad, solange PageSpeed Insights noch den alten Namen prüft (Rückbau: #1433). */
    public const string PFAD_AI_CATALOG = '/.well-known/ai-catalog.json';

    /** Content-Type beider Manifest-Pfade (Standard-Medientyp im ARD-Konformitätstest). */
    public const string CONTENT_TYPE = 'application/ai-catalog+json; charset=utf-8';

    /** Medientyp der Einträge aus markierten Routen und Generatoren. */
    public const string TYP_CAPABILITY = 'application/vnd.ithilbert.ai-manifest-capability+json';

    /** Präfix jedes identifier. */
    public const string URN_PRAEFIX = 'urn:air';

    /** Grenzen für representativeQueries laut Konformitätstest. */
    public const int QUERIES_MIN = 2;

    public const int QUERIES_MAX = 5;

    private function __construct() {}
}

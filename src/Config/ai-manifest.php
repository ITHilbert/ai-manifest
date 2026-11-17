<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Name und Beschreibung
    |--------------------------------------------------------------------------
    | Für die erzeugte llms.txt (Überschrift und Zitatzeile).
    | name: null → config('app.name').
    */
    'name' => null,

    'description' => '',

    /*
    |--------------------------------------------------------------------------
    | Publisher im identifier (urn:air:<publisher>:<namespace>:<name>)
    |--------------------------------------------------------------------------
    | null → Host aus config('app.url') in Kleinbuchstaben ohne Port,
    | ohne Host 'localhost'.
    */
    'publisher' => null,

    /*
    |--------------------------------------------------------------------------
    | Namespace der Einträge aus Routen und Generatoren
    |--------------------------------------------------------------------------
    */
    'namespace' => 'routes',

    /*
    |--------------------------------------------------------------------------
    | Manuelle ARD-Einträge
    |--------------------------------------------------------------------------
    | Je Eintrag Pflicht: name, displayName, type und genau eines von url/data.
    | Weitere Schlüssel des Schemas (representativeQueries, description, tags …)
    | gehen unverändert durch. Unvollständige Einträge werden verworfen.
    | entries_namespace: Namespace-Teil ihres identifier.
    */
    'entries_namespace' => 'site',

    'entries' => [],

    /*
    |--------------------------------------------------------------------------
    | llms.txt
    |--------------------------------------------------------------------------
    | Vorrang: datei (absoluter Pfad zu einer Markdown-Datei) > inhalt (String)
    | > erzeugt aus name/description und links ([title, url, desc?]).
    | Nichts davon gesetzt oder Datei nicht lesbar → /llms.txt antwortet 404.
    */
    'llms_txt' => [
        'datei' => null,
        'inhalt' => null,
        'links' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware für alle Routen des Pakets (ard.json, llms.txt)
    |--------------------------------------------------------------------------
    */
    'middleware' => [],

];

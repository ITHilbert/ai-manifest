<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use DOMDocument;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use ITHilbert\AiManifest\Tests\TestCase;

final class ArdLinkKomponenteTest extends TestCase
{
    /**
     * @return list<array{string, string}> rel- und href-Werte aller <link>-Elemente
     */
    private function links(): array
    {
        $html = Blade::render('<head><x-ai-manifest::ard-link /></head>');
        $dom = new DOMDocument;
        @$dom->loadHTML('<!DOCTYPE html><html>'.$html.'<body></body></html>');

        $links = [];
        foreach ($dom->getElementsByTagName('link') as $link) {
            $links[] = [$link->getAttribute('rel'), $link->getAttribute('href')];
        }

        return $links;
    }

    // AK8
    public function test_komponente_rendert_link_rel_ard_mit_absoluter_url(): void
    {
        $this->assertSame([['ard', url('/.well-known/ard.json')]], $this->links());
        $this->assertStringStartsWith('http', $this->links()[0][1]);

        // die URL folgt der App (Quelle ändern, nicht nur Gleichheit prüfen)
        URL::forceRootUrl('http://andere-seite.test');
        $this->assertSame([['ard', 'http://andere-seite.test/.well-known/ard.json']], $this->links());
    }
}

<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use ITHilbert\AiManifest\Tests\TestCase;

/**
 * README-Pruefung ueber Vorhandensein von Begriffen (kein Wortlaut), Stil wie kontaktformular WebmcpFormularTest.
 */
final class ArdReadmeTest extends TestCase
{
    private function readme(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2).'/README.md');
    }

    // AK13
    public function test_readme_dokumentiert_route_eintrag_config_komponente_und_llms_txt(): void
    {
        $readme = $this->readme();
        // Gegenprobe: dieselbe Pruefung findet bestehende Doku
        $this->assertStringContainsString('ai-manifest:generate', $readme);

        foreach ([
            '/.well-known/ard.json', 'application/vnd.ithilbert.ai-manifest-capability+json',
            '->queries(', 'representativeQueries',
            'publisher', 'entries_namespace', 'ai_catalog_legacy', 'AI_MANIFEST_LEGACY_AI_CATALOG',
            'llms_txt', 'datei', 'inhalt', 'links',
            '<x-ai-manifest::ard-link', 'rel="ard"', 'llms.txt', '/.well-known/ai-catalog.json',
            '0001',
        ] as $begriff) {
            $this->assertStringContainsString($begriff, $readme, "README nennt '{$begriff}' nicht");
        }
        foreach (['namespace', 'entries', 'cache_ttl', 'middleware'] as $schluessel) {
            $this->assertMatchesRegularExpression('/`'.$schluessel.'`/', $readme, "Config-Schluessel {$schluessel} nicht dokumentiert");
        }
    }

    // AK13
    public function test_readme_nennt_uebergangspfad_rueckbau_leere_liste_und_umstieg(): void
    {
        $readme = $this->readme();

        $this->assertMatchesRegularExpression('/ai-catalog\.json.{0,600}(PageSpeed|Rückbau)|(PageSpeed|Rückbau).{0,600}ai-catalog\.json/su', $readme);
        $this->assertMatchesRegularExpression('/Firmen-Website.{0,600}(leere Liste|\{"entries":\s*\[\]\})|(leere Liste|\{"entries":\s*\[\]\}).{0,600}Firmen-Website/su', $readme);
        $this->assertMatchesRegularExpression('/1\.x.{0,500}2\.0|2\.0.{0,500}1\.x/su', $readme);
        $this->assertMatchesRegularExpression('/(ai-manifest\.json.{0,300}entfällt|entfällt.{0,300}ai-manifest\.json)/su', $readme);
    }

    // Randfall 3
    public function test_readme_nennt_statische_dateien_die_vor_laravel_ausgeliefert_werden(): void
    {
        $this->assertMatchesRegularExpression('/public\/llms\.txt.{0,400}(statisch|Webserver)|(statisch|Webserver).{0,400}public\/llms\.txt/su', $this->readme());
    }
}

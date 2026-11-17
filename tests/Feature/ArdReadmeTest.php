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

    // AK13, AK12
    public function test_readme_dokumentiert_route_eintrag_config_komponente_und_llms_txt(): void
    {
        $readme = $this->readme();
        // Gegenprobe: dieselbe Pruefung findet bestehende Doku
        $this->assertStringContainsString('ai-manifest:generate', $readme);

        foreach ([
            '/.well-known/ard.json', 'application/vnd.ithilbert.ai-manifest-capability+json',
            '->queries(', 'representativeQueries',
            'publisher', 'entries_namespace',
            'llms_txt', 'datei', 'inhalt', 'links',
            '<x-ai-manifest::ard-link', 'rel="ard"', 'llms.txt',
            '0001',
        ] as $begriff) {
            $this->assertStringContainsString($begriff, $readme, "README nennt '{$begriff}' nicht");
        }
        foreach (['namespace', 'entries', 'middleware'] as $schluessel) {
            $this->assertMatchesRegularExpression('/`'.$schluessel.'`/', $readme, "Config-Schluessel {$schluessel} nicht dokumentiert");
        }
        // AK12: cache_ttl ist kein Config-Schluessel mehr (weder Tabellenzeile noch unter "Bleibt:")
        $this->assertDoesNotMatchRegularExpression('/^\|\s*`cache_ttl`/m', $readme);
        $this->assertDoesNotMatchRegularExpression('/Bleibt:.{0,400}`cache_ttl`/su', $readme);
        // AK7: Config-Schluessel und env des Uebergangspfads stehen nicht mehr in der Config-Doku
        $this->assertDoesNotMatchRegularExpression('/^\|\s*`ai_catalog_legacy`/m', $readme);
        $this->assertStringNotContainsString('AI_MANIFEST_LEGACY_AI_CATALOG=false', $readme);
        $this->assertDoesNotMatchRegularExpression('/Rückbau-Bedingung/u', $readme);
        $this->assertDoesNotMatchRegularExpression('/Neu:.{0,300}ai_catalog_legacy/su', $readme);
    }

    // AK13, AK7
    public function test_readme_nennt_uebergangspfad_nur_als_entfallen_leere_liste_und_umstieg(): void
    {
        $readme = $this->readme();

        // AK7: jede Nennung von ai-catalog.json liegt im Abschnitt Umstieg 1.x/2.0 -> 2.1 und steht dort als entfallen
        $this->assertStringContainsString('ai-catalog.json', $readme);
        $this->assertMatchesRegularExpression('/(ai-catalog\.json.{0,300}entfällt|entfällt.{0,300}ai-catalog\.json)/su', $readme);
        $teile = preg_split('/^##\s+[^\n]*Umstieg[^\n]*$/mu', $readme, 2);
        $this->assertCount(2, $teile, 'Abschnitt Umstieg fehlt');
        $umstieg = preg_split('/^## /m', $teile[1], 2)[0];
        $ausserhalb = str_replace($umstieg, '', $readme);
        $this->assertStringContainsString('ai-catalog.json', $umstieg);
        $this->assertStringNotContainsString('ai-catalog.json', $ausserhalb);
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

<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use ITHilbert\AiManifest\Tests\TestCase;

/**
 * Annahme fuer den Coder: `links` liegt in der Config `ai-manifest.llms_txt` (neben datei, inhalt);
 * `name`/`description` kommen aus `ai-manifest.name` (sonst app.name) und `ai-manifest.description`.
 * Die Config wird zur Laufzeit gelesen. Leerzeilen zwischen Listenzeilen sind nicht festgelegt.
 */
final class LlmsTxtTest extends TestCase
{
    private string $verzeichnis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->verzeichnis = sys_get_temp_dir().'/ai-manifest-llms-'.uniqid('', true);
        (new Filesystem)->makeDirectory($this->verzeichnis, 0755, true);
        config()->set('ai-manifest.name', 'Meine Firma');
        config()->set('ai-manifest.description', '');
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->verzeichnis);
        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $werte
     */
    private function llms(array $werte): void
    {
        config()->set('ai-manifest.llms_txt', array_merge(['datei' => null, 'inhalt' => null, 'links' => []], $werte));
    }

    // AK9
    public function test_vorrang_datei_vor_inhalt_vor_erzeugt_mit_text_plain(): void
    {
        $datei = $this->verzeichnis.'/llms.md';
        file_put_contents($datei, "# Aus Datei\n\nDateiinhalt mit mindestens fuenfzig Zeichen Laenge fuer den Test.\n");
        $links = [['title' => 'Start', 'url' => 'https://x.test/']];

        $this->llms(['datei' => $datei, 'inhalt' => '# Aus Inhalt', 'links' => $links]);
        $response = $this->get('/llms.txt');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->assertSame((string) file_get_contents($datei), $response->getContent());

        $this->llms(['inhalt' => '# Aus Inhalt', 'links' => $links]);
        $this->assertSame('# Aus Inhalt', $this->get('/llms.txt')->assertOk()->getContent());

        $this->llms(['links' => $links]);
        $this->assertStringStartsWith('# Meine Firma', (string) $this->get('/llms.txt')->assertOk()->getContent());
    }

    // AK9
    public function test_erzeugt_aus_name_beschreibung_und_links(): void
    {
        config()->set('ai-manifest.description', 'Kurzbeschreibung');
        $this->llms(['links' => [
            ['title' => 'Start', 'url' => 'https://x.test/', 'desc' => 'Hauptseite'],
            ['title' => 'Kontakt', 'url' => 'https://x.test/kontakt'],
        ]]);

        $body = (string) $this->get('/llms.txt')->assertOk()->getContent();

        $this->assertStringStartsWith("# Meine Firma\n\n> Kurzbeschreibung\n\n## Links\n", $body);
        $zeilen = array_values(array_filter(array_map('trim', explode("\n", $body)), fn ($z) => $z !== ''));
        $this->assertSame([
            '- [Start](https://x.test/): Hauptseite',
            '- [Kontakt](https://x.test/kontakt)',
        ], array_slice($zeilen, 3));

        // ohne Beschreibung keine Zitatzeile
        config()->set('ai-manifest.description', '');
        $this->assertStringNotContainsString("\n>", "\n".$this->get('/llms.txt')->getContent());
    }

    // AK9
    public function test_ohne_inhalt_links_oder_mit_nicht_lesbarer_datei_ist_404(): void
    {
        // Gegenprobe: mit Links ist die Route erreichbar
        $this->llms(['links' => [['title' => 'Start', 'url' => 'https://x.test/']]]);
        $this->get('/llms.txt')->assertOk();

        $this->llms([]);
        $this->get('/llms.txt')->assertNotFound();

        config()->set('ai-manifest.description', 'Beschreibung allein genuegt nicht');
        $this->get('/llms.txt')->assertNotFound();

        $this->llms(['datei' => $this->verzeichnis.'/gibt-es-nicht.md']);
        $this->get('/llms.txt')->assertNotFound();
    }
}

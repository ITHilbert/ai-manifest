<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Ard;

use Illuminate\Contracts\Config\Repository;
use ITHilbert\AiManifest\Data\CapabilityEntry;
use ITHilbert\AiManifest\Services\AiManifestService;

/**
 * Baut den Body des ARD-Manifests: Einträge aus markierten Routen und Generatoren,
 * danach die manuellen Einträge aus der Config `ai-manifest.entries`.
 */
final readonly class ArdManifest
{
    public function __construct(
        private AiManifestService $service,
        private Repository $config,
    ) {}

    /**
     * @return array{entries: list<array<string, mixed>>}
     */
    public function body(): array
    {
        $publisher = ArdIdentifier::publisher(
            $this->config->get('ai-manifest.publisher'),
            $this->config->get('app.url'),
        );

        $eintraege = [];

        $namespace = $this->namespace('ai-manifest.namespace', 'routes');
        foreach ($this->service->getCapabilities() as $capability) {
            $eintraege[] = $this->ausCapability($capability, $publisher, $namespace);
        }

        $namespaceManuell = $this->namespace('ai-manifest.entries_namespace', 'site');
        foreach ((array) $this->config->get('ai-manifest.entries', []) as $manuell) {
            $eintrag = is_array($manuell) ? $this->ausConfig($manuell, $publisher, $namespaceManuell) : null;

            if ($eintrag !== null) {
                $eintraege[] = $eintrag;
            }
        }

        return ['entries' => array_map(
            static fn (ArdEintrag $eintrag): array => $eintrag->toArray(),
            $this->eindeutig($eintraege),
        )];
    }

    /**
     * Stabiler Fingerabdruck eines Bodys (Teil des Cache-Schlüssels).
     *
     * @param  array<string, mixed>  $body
     */
    public static function fingerabdruck(array $body): string
    {
        return md5(self::json($body));
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public static function json(array $body, bool $lesbar = false): string
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

        return json_encode($body, $lesbar ? $flags | JSON_PRETTY_PRINT : $flags);
    }

    private function ausCapability(CapabilityEntry $capability, string $publisher, string $namespace): ArdEintrag
    {
        $displayName = $capability->summary !== '' ? $capability->summary : $capability->id;

        return new ArdEintrag(
            identifier: ArdIdentifier::urn($publisher, $namespace, $capability->id),
            displayName: $displayName,
            type: ArdFormat::TYP_CAPABILITY,
            data: $capability->toArray(),
            weitere: ['representativeQueries' => $this->queries($capability, $displayName)],
        );
    }

    /**
     * Manueller Eintrag; ohne Pflichtangaben oder mit url und data zugleich verworfen (null).
     *
     * @param  array<mixed>  $roh
     */
    private function ausConfig(array $roh, string $publisher, string $namespace): ?ArdEintrag
    {
        foreach (['name', 'displayName', 'type'] as $pflicht) {
            if (! isset($roh[$pflicht]) || ! is_string($roh[$pflicht]) || trim($roh[$pflicht]) === '') {
                return null;
            }
        }

        $url = $roh['url'] ?? null;
        $data = $roh['data'] ?? null;

        if (($url === null) === ($data === null) || ($url !== null && ! is_string($url)) || ($data !== null && ! is_array($data))) {
            return null;
        }

        $weitere = array_diff_key($roh, array_flip(['name', 'identifier', 'displayName', 'type', 'url', 'data']));

        return new ArdEintrag(
            identifier: ArdIdentifier::urn($publisher, $namespace, $roh['name']),
            displayName: $roh['displayName'],
            type: $roh['type'],
            url: $url,
            data: $data,
            weitere: array_filter($weitere, is_string(...), ARRAY_FILTER_USE_KEY),
        );
    }

    /**
     * Angegebene Queries (nicht leer, ohne Dubletten, höchstens fünf);
     * weniger als zwei: ersatzweise displayName und "<METHOD> <path>".
     *
     * @return list<string>
     */
    private function queries(CapabilityEntry $capability, string $displayName): array
    {
        $queries = array_slice($this->ohneDubletten($capability->queries), 0, ArdFormat::QUERIES_MAX);

        if (count($queries) >= ArdFormat::QUERIES_MIN) {
            return $queries;
        }

        return $this->ohneDubletten([$displayName, $capability->method.' '.$capability->path]);
    }

    /**
     * @param  list<string>  $werte
     * @return list<string>
     */
    private function ohneDubletten(array $werte): array
    {
        return array_values(array_unique(array_filter($werte, static fn (string $q): bool => trim($q) !== '')));
    }

    /**
     * Gleicher identifier: der zweite bekommt `-2`, der dritte `-3` …
     *
     * @param  list<ArdEintrag>  $eintraege
     * @return list<ArdEintrag>
     */
    private function eindeutig(array $eintraege): array
    {
        $vergeben = [];
        $ergebnis = [];

        foreach ($eintraege as $eintrag) {
            $identifier = $eintrag->identifier;

            for ($zaehler = 2; isset($vergeben[$identifier]); $zaehler++) {
                $identifier = $eintrag->identifier.'-'.$zaehler;
            }

            $vergeben[$identifier] = true;
            $ergebnis[] = $identifier === $eintrag->identifier ? $eintrag : $eintrag->mitIdentifier($identifier);
        }

        return $ergebnis;
    }

    private function namespace(string $schluessel, string $standard): string
    {
        $wert = $this->config->get($schluessel);

        return ArdIdentifier::segment(is_string($wert) ? $wert : '', $standard);
    }
}

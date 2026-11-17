<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use ITHilbert\AiManifest\Ard\ArdFormat;
use ITHilbert\AiManifest\Ard\ArdManifest;

final class AiManifestGenerateCommand extends Command
{
    protected $signature = 'ai-manifest:generate {--path= : Zielpfad für das JSON-File (Default: public/.well-known/ard.json)}';

    protected $description = 'Schreibt das ARD-Manifest (ard.json) als statische Datei.';

    public function __construct(
        private readonly ArdManifest $manifest,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Erzeuge ARD-Manifest ...');

        $targetPath = $this->resolveTargetPath();
        $this->ensureDirectoryExists(dirname($targetPath));

        $body = $this->manifest->body();
        $this->filesystem->put($targetPath, ArdManifest::json($body, lesbar: true));

        $count = count($body['entries']);
        $this->info(sprintf('Fertig: %s — %d %s geschrieben.', $targetPath, $count, $count === 1 ? 'Eintrag' : 'Einträge'));

        return self::SUCCESS;
    }

    private function resolveTargetPath(): string
    {
        $pathOption = $this->option('path');

        if (is_string($pathOption) && $pathOption !== '') {
            return $pathOption;
        }

        return public_path(ltrim(ArdFormat::PFAD_ARD, '/'));
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (! $this->filesystem->isDirectory($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }
    }
}

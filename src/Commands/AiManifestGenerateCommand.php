<?php

declare(strict_types=1);

namespace ITHilbert\AiManifest\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use ITHilbert\AiManifest\Services\AiManifestService;

final class AiManifestGenerateCommand extends Command
{
    protected $signature = 'ai-manifest:generate {--path= : Zielpfad für das JSON-File (Default: public/.well-known/ai-manifest.json)}';

    protected $description = 'Generiert das AI-Manifest als statisches JSON-File.';

    public function __construct(
        private readonly AiManifestService $service,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Generiere AI-Manifest...');

        $targetPath = $this->resolveTargetPath();
        $targetDir = dirname($targetPath);

        $this->ensureDirectoryExists($targetDir);

        $json = $this->service->toJson();
        $capabilities = $this->service->getCapabilities();
        $count = count($capabilities);

        $this->filesystem->put($targetPath, $json);

        $this->info(sprintf(
            'Fertig! %s — %d Capabilit%s geschrieben.',
            $targetPath,
            $count,
            $count === 1 ? 'y' : 'ies',
        ));

        return self::SUCCESS;
    }

    private function resolveTargetPath(): string
    {
        $pathOption = $this->option('path');

        if (is_string($pathOption) && $pathOption !== '') {
            return $pathOption;
        }

        return public_path('.well-known/ai-manifest.json');
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (! $this->filesystem->isDirectory($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }
    }
}

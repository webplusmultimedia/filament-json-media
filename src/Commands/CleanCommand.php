<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Commands;

use GalleryJsonMedia\JsonMedia\UrlParser;
use GalleryJsonMedia\Support\Disk;
use GalleryJsonMedia\Support\MediaReferences;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class CleanCommand extends Command
{
    protected $signature = 'gallery-json-media:clean
        {--disk=* : The disks to clean, the disk of the package config by default}
        {--dry-run : List the files to delete without deleting them}
        {--force : Delete the files without asking for confirmation}';

    protected $description = 'Delete the thumbnails whose image is missing and, with the declared models, the files that no record uses';

    public function handle(): int
    {
        $references = MediaReferences::fromDeclaredModels();

        if (! $references->areKnown()) {
            $this->components->warn('No model is declared in gallery-json-media.maintenance.models : only the thumbnails whose image is missing are deleted.');
        }

        $orphans = [];
        foreach ($this->disks() as $disk) {
            if ($files = $this->orphanFiles($disk, $references)) {
                $orphans[$disk] = $files;
            }
        }

        $count = array_sum(array_map(count(...), $orphans));
        if ($count === 0) {
            $this->components->info('Nothing to delete.');

            return self::SUCCESS;
        }

        foreach ($orphans as $disk => $files) {
            foreach ($files as $file) {
                $this->components->twoColumnDetail($file, $disk);
            }
        }

        $files = $count . ' ' . Str::plural('file', $count);

        if ($this->option('dry-run')) {
            $this->components->info("{$files} would be deleted.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Delete {$files}?")) {
            $this->components->info('Nothing deleted.');

            return self::SUCCESS;
        }

        foreach ($orphans as $disk => $paths) {
            Storage::disk($disk)->delete($paths);
        }

        $this->components->info("{$files} deleted.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function disks(): array
    {
        return $this->option('disk') ?: [config('gallery-json-media.disk')];
    }

    /**
     * @return array<int, string>
     */
    private function orphanFiles(string $disk, MediaReferences $references): array
    {
        $files = Storage::disk($disk)->allFiles(Disk::rootDirectory());
        $existing = array_flip($files);
        $parser = UrlParser::make();

        return array_values(array_filter($files, function (string $file) use ($disk, $references, $existing, $parser): bool {
            if ($references->uses($disk, $file)) {
                return false;
            }

            $thumbnail = $parser->parseThumbnailPath($file);
            if ($thumbnail === false) {
                return $references->areKnown();
            }

            // A thumbnail goes with its image
            return $references->areKnown()
                ? ! $references->uses($disk, $thumbnail['path'])
                : ! isset($existing[$thumbnail['path']]);
        }));
    }
}

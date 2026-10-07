<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Commands;

use GalleryJsonMedia\JsonMedia\ImageManipulation\Croppa;
use GalleryJsonMedia\JsonMedia\UrlParser;
use GalleryJsonMedia\Support\Disk;
use GalleryJsonMedia\Support\MediaReferences;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class RegenerateCommand extends Command
{
    protected $signature = 'gallery-json-media:regenerate
        {--disk=* : The disks of the thumbnails, the disk of the package config by default}';

    protected $description = 'Render again the existing thumbnails from their image, after a change of quality or of driver';

    public function handle(): int
    {
        $references = MediaReferences::fromDeclaredModels();
        $failures = [];

        foreach ($this->disks() as $disk) {
            $storage = Storage::disk($disk);
            $thumbnails = $this->thumbnails($disk, $storage, $references);

            $this->components->info("{$disk} : " . count($thumbnails) . ' ' . Str::plural('thumbnail', count($thumbnails)));

            $this->withProgressBar($thumbnails, function (array $thumbnail) use ($disk, $storage, &$failures): void {
                try {
                    $this->render($disk, $storage, $thumbnail);
                } catch (Throwable $exception) {
                    $failures[] = [$thumbnail['file'], $exception->getMessage()];
                }
            });
            $this->newLine(2);
        }

        if ($failures !== []) {
            $this->components->error(count($failures) . ' ' . Str::plural('thumbnail', count($failures)) . ' could not be rendered, they are kept as they were :');
            foreach ($failures as [$file, $message]) {
                $this->components->twoColumnDetail($file, $message);
            }

            return self::FAILURE;
        }

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
     * @return array<int, array{file : string, path : string, width : int|null, height : int|null, format : string|null}>
     */
    private function thumbnails(string $disk, Filesystem $storage, MediaReferences $references): array
    {
        $files = $storage->allFiles(Disk::rootDirectory());
        $existing = array_flip($files);
        $parser = UrlParser::make();
        $thumbnails = [];

        foreach ($files as $file) {
            $thumbnail = $parser->parseThumbnailPath($file);

            // A used file only looks like a thumbnail, and the thumbnail of a missing image cannot be rendered
            if ($thumbnail === false || $references->uses($disk, $file) || ! isset($existing[$thumbnail['path']])) {
                continue;
            }

            $thumbnails[] = ['file' => $file, ...$thumbnail];
        }

        return $thumbnails;
    }

    /**
     * @param  array{file : string, path : string, width : int|null, height : int|null, format : string|null}  $thumbnail
     */
    private function render(string $disk, Filesystem $storage, array $thumbnail): void
    {
        // The thumbnail keeps its visibility, buckets without ACL do not give it
        $visibility = rescue(fn () => $storage->getVisibility($thumbnail['file']), 'public', report: false);

        (new Croppa($storage, $thumbnail['path'], $thumbnail['width'], $thumbnail['height'], $disk, $visibility, $thumbnail['format']))->render();
    }
}

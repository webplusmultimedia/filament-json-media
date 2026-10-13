<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Commands;

use GalleryJsonMedia\JsonMedia\ImageManipulation\Croppa;
use GalleryJsonMedia\JsonMedia\Media;
use GalleryJsonMedia\JsonMedia\UrlParser;
use GalleryJsonMedia\Support\Disk;
use GalleryJsonMedia\Support\ImageDimensions;
use GalleryJsonMedia\Support\MediaReferences;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class RegenerateCommand extends Command
{
    protected $signature = 'gallery-json-media:regenerate
        {--disk=* : The disks of the thumbnails and of the images, the disk of the package config by default}
        {--thumbnails-only : Only render the thumbnails again}
        {--dimensions-only : Only record the size of the images saved without it}';

    protected $description = 'Render again the existing thumbnails from their image, after a change of quality or of driver, and record the size of the images saved without it';

    public function handle(): int
    {
        if ($this->option('thumbnails-only') && $this->option('dimensions-only')) {
            $this->components->error('The options --thumbnails-only and --dimensions-only exclude each other.');

            return self::INVALID;
        }

        $failures = $this->option('dimensions-only') ? [] : $this->renderThumbnails();
        $unreadable = $this->option('thumbnails-only') ? [] : $this->completeDimensions();

        $this->reportFailures($failures, 'thumbnail', 'could not be rendered, they are kept as they were');
        $this->reportFailures($unreadable, 'image', 'could not be read, their size stays unknown');

        return $failures === [] && $unreadable === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<int, array{0 : string, 1 : string}> the thumbnails that could not be rendered, with the reason
     */
    private function renderThumbnails(): array
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

        return $failures;
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

    /**
     * The images of the declared models saved without their size get it from their file : it keeps their thumbnails
     * from being larger than them. The json is written as is, without the model events nor a new updated_at.
     *
     * @return array<int, array{0 : string, 1 : string}> the images that could not be read, with the reason
     */
    private function completeDimensions(): array
    {
        /** @var array<class-string<Model>, array<int, string>> $models */
        $models = config('gallery-json-media.maintenance.models', []);

        if ($models === []) {
            $this->components->warn('Declare the json media fields in gallery-json-media.maintenance.models to also record the size of their images.');

            return [];
        }

        $failures = [];
        $completed = 0;

        foreach ($models as $model => $fields) {
            $key = (new $model)->getKeyName();

            // Every row counts : a soft deleted or a scoped record keeps its images
            $model::query()
                ->withoutGlobalScopes()
                ->select([$key, ...$fields])
                ->lazyById(column: $key)
                ->each(function (Model $record) use ($model, $fields, &$failures, &$completed): void {
                    $changes = [];

                    foreach ($fields as $field) {
                        $entries = json_decode((string) $record->getRawOriginal($field), true);

                        if (is_array($entries) && ($entries = $this->withDimensions($entries, $failures, $completed)) !== null) {
                            $changes[$field] = json_encode($entries, JSON_THROW_ON_ERROR);
                        }
                    }

                    if ($changes !== []) {
                        $model::query()->withoutGlobalScopes()->whereKey($record->getKey())->toBase()->update($changes);
                    }
                });
        }

        $this->components->info("{$completed} " . Str::plural('image', $completed) . ' completed with their size');

        return $failures;
    }

    /**
     * @param  array<array-key, mixed>  $entries
     * @param  array<int, array{0 : string, 1 : string}>  $failures
     * @return array<array-key, mixed>|null the entries, null when none of them changed
     */
    private function withDimensions(array $entries, array &$failures, int &$completed): ?array
    {
        $changed = false;

        foreach ($entries as $index => $entry) {
            if (! is_array($entry) || ! is_string($file = $entry['file'] ?? null) || ! $this->lacksDimensions($entry)) {
                continue;
            }

            try {
                // An entry saved without disk is on the default disk, as the medias read it
                $storage = Storage::disk($entry['disk'] ?? null);
                // A local image is read in place, a remote one is copied : only its header is read
                $dimensions = Disk::isLocal($storage)
                    ? ImageDimensions::fromPath($storage->path($file))
                    : ImageDimensions::fromStream($storage->readStream($file));

                if ($dimensions === null) {
                    throw new RuntimeException('The file is missing or its size cannot be read.');
                }

                $entries[$index] = [...$entry, ...$dimensions->toArray()];
                $changed = true;
                $completed++;
            } catch (Throwable $exception) {
                $failures[] = [$file, $exception->getMessage()];
            }
        }

        return $changed ? $entries : null;
    }

    /**
     * A bitmap image of the selected disks whose size is unknown : a svg, never resized, needs none.
     *
     * @param  array<array-key, mixed>  $entry
     */
    private function lacksDimensions(array $entry): bool
    {
        $mimeType = (string) data_get($entry, 'mime_type', 'image/webp');
        $disk = $entry['disk'] ?? config('filesystems.default');

        return Media::isImage($mimeType)
            && $mimeType !== 'image/svg+xml'
            && in_array($disk, $this->disks(), true)
            && ImageDimensions::fromEntry($entry) === null;
    }

    /**
     * @param  array<int, array{0 : string, 1 : string}>  $failures
     */
    private function reportFailures(array $failures, string $subject, string $consequence): void
    {
        if ($failures === []) {
            return;
        }

        $this->components->error(count($failures) . ' ' . Str::plural($subject, count($failures)) . " {$consequence} :");
        foreach ($failures as [$file, $message]) {
            $this->components->twoColumnDetail($file, $message);
        }
    }
}

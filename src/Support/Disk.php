<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;

final class Disk
{
    /**
     * Public files get their direct url, private ones a temporary url.
     */
    public static function url(Filesystem $storage, string $path, string $visibility): string
    {
        /** @var FilesystemAdapter $storage */
        if ($visibility === 'private') {
            return $storage->temporaryUrl($path, now()->addMinutes(self::temporaryUrlTtl()));
        }

        return $storage->url($path);
    }

    public static function isLocal(Filesystem $storage): bool
    {
        return $storage instanceof FilesystemAdapter && $storage->getAdapter() instanceof LocalFilesystemAdapter;
    }

    /**
     * The directory of the uploaded files, out of which the maintenance commands never go.
     */
    public static function rootDirectory(): string
    {
        return (string) config('gallery-json-media.root_directory', 'web-attachments');
    }

    /**
     * Lifetime, in minutes, of the temporary urls of private files.
     */
    public static function temporaryUrlTtl(): int
    {
        return (int) config('gallery-json-media.images.temporary_url_ttl', 5);
    }
}

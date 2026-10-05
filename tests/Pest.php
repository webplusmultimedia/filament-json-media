<?php

declare(strict_types=1);

use GalleryJsonMedia\Tests\TestCase;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

uses(TestCase::class)->in(__DIR__);

/**
 * Store a real image on a disk and return its json media entry.
 *
 * @return array<string, mixed>
 */
function storedImage(string $path, int $width = 800, int $height = 600, string $alt = 'A photo', string $disk = 'public', ?string $visibility = null): array
{
    $image = UploadedFile::fake()->image(basename($path), $width, $height);
    Storage::disk($disk)->putFileAs(dirname($path), $image, basename($path));

    return [
        'file' => $path,
        'disk' => $disk,
        ...($visibility === null ? [] : ['visibility' => $visibility]),
        'mime_type' => $image->getMimeType(),
        'size' => $image->getSize(),
        'customProperties' => ['alt' => $alt, 'title' => null],
    ];
}

/**
 * Store a document on a disk and return its json media entry.
 *
 * @return array<string, mixed>
 */
function storedDocument(string $path, string $alt = 'A document', string $disk = 'public', ?string $visibility = null): array
{
    Storage::disk($disk)->put($path, '%PDF-1.4');

    return [
        'file' => $path,
        'disk' => $disk,
        ...($visibility === null ? [] : ['visibility' => $visibility]),
        'mime_type' => 'application/pdf',
        'size' => 8,
        'customProperties' => ['alt' => $alt, 'title' => null],
    ];
}

/**
 * Register an in memory disk that behaves like a remote one (S3) : no local path,
 * public urls on https://bucket.test and temporary urls carrying their expiration.
 */
function fakeRemoteDisk(string $name = 's3'): FilesystemAdapter
{
    // Laravel reads the urls from the adapter, as it does for the S3 one
    $adapter = new class extends InMemoryFilesystemAdapter
    {
        public function getUrl(string $path): string
        {
            return "https://bucket.test/{$path}";
        }

        public function getTemporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
        {
            return "https://bucket.test/{$path}?expires={$expiration->getTimestamp()}";
        }
    };
    $disk = new FilesystemAdapter(new Filesystem($adapter), $adapter);

    Storage::set($name, $disk);

    return $disk;
}

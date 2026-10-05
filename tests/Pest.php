<?php

declare(strict_types=1);

use GalleryJsonMedia\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(TestCase::class)->in(__DIR__);

/**
 * Store a real image on the public disk and return its json media entry.
 *
 * @return array{file: string, disk: string, mime_type: string, size: int, customProperties: array{alt: string, title: null}}
 */
function storedImage(string $path, int $width = 800, int $height = 600, string $alt = 'A photo'): array
{
    $image = UploadedFile::fake()->image(basename($path), $width, $height);
    Storage::disk('public')->putFileAs(dirname($path), $image, basename($path));

    return [
        'file' => $path,
        'disk' => 'public',
        'mime_type' => $image->getMimeType(),
        'size' => $image->getSize(),
        'customProperties' => ['alt' => $alt, 'title' => null],
    ];
}

/**
 * Store a document on the public disk and return its json media entry.
 *
 * @return array{file: string, disk: string, mime_type: string, size: int, customProperties: array{alt: string, title: null}}
 */
function storedDocument(string $path, string $alt = 'A document'): array
{
    Storage::disk('public')->put($path, '%PDF-1.4');

    return [
        'file' => $path,
        'disk' => 'public',
        'mime_type' => 'application/pdf',
        'size' => 8,
        'customProperties' => ['alt' => $alt, 'title' => null],
    ];
}

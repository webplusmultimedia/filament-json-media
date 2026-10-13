<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\ImageManipulation\Croppa;
use GalleryJsonMedia\Support\ImageDimensions;
use Illuminate\Support\Facades\Storage;

/**
 * A jpeg whose exif segment holds only its orientation, as the cameras record a turned photo.
 */
function jpegWithOrientation(int $width, int $height, int $orientation): string
{
    ob_start();
    imagejpeg(imagecreatetruecolor($width, $height));
    $jpeg = (string) ob_get_clean();

    // A big endian tiff header, then an ifd of one entry : the orientation tag (0x0112), a short
    $tiff = "MM\x00\x2A\x00\x00\x00\x08\x00\x01\x01\x12\x00\x03\x00\x00\x00\x01" . pack('n', $orientation) . "\x00\x00\x00\x00\x00\x00";
    $exif = "Exif\x00\x00" . $tiff;

    return substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif . substr($jpeg, 2);
}

it('reads the size of an image from its header', function () {
    Storage::fake('public');
    storedImage('page/photo.jpg', 800, 600);

    expect(ImageDimensions::fromPath(Storage::disk('public')->path('page/photo.jpg')))->toEqual(new ImageDimensions(800, 600));
});

it('swaps the sides of an image turned a quarter by its exif orientation', function (int $orientation, array $size) {
    Storage::fake('public');
    Storage::disk('public')->put('page/photo.jpg', jpegWithOrientation(800, 600, $orientation));

    expect(ImageDimensions::fromPath(Storage::disk('public')->path('page/photo.jpg'))?->toArray())->toBe($size);
})->with([
    'upright' => [1, ['width' => 800, 'height' => 600]],
    'upside down' => [3, ['width' => 800, 'height' => 600]],
    'quarter turn right' => [6, ['width' => 600, 'height' => 800]],
    'quarter turn left' => [8, ['width' => 600, 'height' => 800]],
]);

it('reads the size of a turned image as its thumbnails see it', function () {
    Storage::fake('public');
    Storage::disk('public')->put('page/portrait.jpg', jpegWithOrientation(800, 600, 6));

    (new Croppa(Storage::disk('public'), 'page/portrait.jpg', 300))->render();

    expect(array_slice(getimagesize(Storage::disk('public')->path('page/portrait-300x_.jpg')), 0, 2))->toBe([300, 400])
        ->and(ImageDimensions::fromPath(Storage::disk('public')->path('page/portrait.jpg')))->toEqual(new ImageDimensions(600, 800));
});

it('reads no size from a file that is not an image', function (?string $contents) {
    Storage::fake('public');
    if ($contents !== null) {
        Storage::disk('public')->put('page/file', $contents);
    }

    expect(ImageDimensions::fromPath(Storage::disk('public')->path('page/file')))->toBeNull();
})->with([
    'text' => ['not an image'],
    'missing file' => [null],
]);

it('reads the size of an image from a stream and closes it', function () {
    $disk = fakeRemoteDisk();
    storedImage('page/photo.jpg', 800, 600, disk: 's3');
    $stream = $disk->readStream('page/photo.jpg');

    expect(ImageDimensions::fromStream($stream))->toEqual(new ImageDimensions(800, 600))
        ->and(is_resource($stream))->toBeFalse();
});

it('reads no size without a stream', function () {
    expect(ImageDimensions::fromStream(null))->toBeNull();
});

it('reads the size recorded in a json media entry', function (array $entry, ?array $size) {
    expect(ImageDimensions::fromEntry($entry)?->toArray())->toBe($size);
})->with([
    'recorded' => [['width' => 800, 'height' => 600], ['width' => 800, 'height' => 600]],
    'not recorded' => [[], null],
    'width only' => [['width' => 800], null],
    'not integers' => [['width' => '800', 'height' => '600'], null],
    'empty' => [['width' => 0, 'height' => 600], null],
]);

it('gives the widest thumbnail that does not enlarge the image', function (int $width, ?int $height, int $max) {
    expect((new ImageDimensions(800, 600))->maxWidthFor($width, $height))->toBe($max);
})->with([
    'width only' => [1200, null, 800],
    'ratio wider than the image' => [1600, 600, 800],
    'ratio taller than the image' => [400, 400, 600],
]);

it('refuses an image without width or height', function () {
    expect(fn () => new ImageDimensions(0, 600))->toThrow(InvalidArgumentException::class);
});

<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\Media;
use Illuminate\Support\Facades\Storage;

it('returns the url of the stored image', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg'));

    expect($media->getUrl())->toBe('/storage/page/photo.jpg')
        ->and((string) $media)->toBe('/storage/page/photo.jpg');
});

it('returns no url and no crop url when the file is missing from the disk', function () {
    Storage::fake('public');
    $entry = storedImage('page/photo.jpg');
    Storage::disk('public')->delete('page/photo.jpg');

    $media = Media::make($entry);

    expect($media->getUrl())->toBeNull()
        ->and($media->getCropUrl(200, 150))->toBe('')
        ->and((string) $media)->toBe('');
});

it('returns a signed thumbnail url for a bitmap image', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg'));

    expect($media->getCropUrl(200, 150))
        ->toBe('/storage/page/photo-200x150.jpg?_token=5a309cfd8a927af6f2c418bcd131c487');
});

it('returns the original url instead of a thumbnail for an svg image', function () {
    Storage::fake('public');
    Storage::disk('public')->put('page/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
    $media = Media::make(['file' => 'page/logo.svg', 'disk' => 'public', 'mime_type' => 'image/svg+xml']);

    expect($media->getCropUrl(200, 150))->toBe('/storage/page/logo.svg');
});

it('renders an image with the thumbnail url, the alt text and the requested size', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg', alt: 'Sunset'));

    $html = $media->withImageProperties(width: 300, height: 230, imgClass: 'rounded')->toHtml();

    expect($html)
        ->toContain('src="/storage/page/photo-300x230.jpg?_token=116354d576564cedd30c850f6a2f2dee"')
        ->toContain('alt="Sunset"')
        ->toContain('width="300"')
        ->toContain('height="230"')
        ->toContain('rounded');
});

it('renders the media with a custom view', function () {
    Storage::fake('public');
    view()->addNamespace('fixtures', __DIR__ . '/../Fixtures/views');
    $media = Media::make(storedImage('page/photo.jpg', alt: 'Sunset'));

    $html = $media->withView('fixtures::custom-media')->toHtml();

    expect($html)->toContain('<figcaption>Sunset</figcaption>');
});

it('escapes the alt text when rendering an image', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg', alt: '"><script>alert(1)</script>'));

    $html = $media->toHtml();

    expect($html)
        ->toContain('&lt;script&gt;')
        ->not->toContain('<script>alert(1)</script>');
});

it('deletes the original and its thumbnails', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg'));
    Storage::disk('public')->put('page/photo-200x150.jpg', 'thumbnail');

    $media->delete();

    Storage::disk('public')->assertMissing(['page/photo.jpg', 'page/photo-200x150.jpg']);
});

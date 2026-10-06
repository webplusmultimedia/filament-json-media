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

    expect($media->getCropUrl(200, 150))->toStartWith('/storage/page/photo-200x150.jpg?disk=public&signature=');
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
        ->toContain('src="/storage/page/photo-300x230.jpg?disk=public&amp;signature=')
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

it('returns the direct url of a public image on a remote disk', function (?string $visibility) {
    fakeRemoteDisk();
    $media = Media::make(storedImage('page/photo.jpg', disk: 's3', visibility: $visibility));

    expect($media->getUrl())->toBe('https://bucket.test/page/photo.jpg');
})->with([
    'visibility not recorded' => [null],
    'public visibility' => ['public'],
]);

it('returns a temporary url for a private image', function () {
    $this->travelTo('2026-01-01 00:00:00');
    fakeRemoteDisk();
    $media = Media::make(storedImage('page/photo.jpg', disk: 's3', visibility: 'private'));

    expect($media->getUrl())->toBe('https://bucket.test/page/photo.jpg?expires=1767225900');
});

it('returns the url of a remote image without checking that it exists', function () {
    $disk = fakeRemoteDisk();
    $entry = storedImage('page/photo.jpg', disk: 's3');
    $disk->delete('page/photo.jpg');

    expect(Media::make($entry)->getUrl())->toBe('https://bucket.test/page/photo.jpg');
});

it('lists thumbnails of the same ratio up to twice the requested width for a srcset', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.images.responsive.widths', [320, 640, 960, 1920]);
    $media = Media::make(storedImage('page/photo.jpg'));

    expect($media->getSrcset(600, 300))->toBe(implode(', ', [
        $media->getCropUrl(320, 160) . ' 320w',
        $media->getCropUrl(600, 300) . ' 600w',
        $media->getCropUrl(640, 320) . ' 640w',
        $media->getCropUrl(960, 480) . ' 960w',
        $media->getCropUrl(1200, 600) . ' 1200w',
    ]));
});

it('lists thumbnails without height for a srcset of a width only', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.images.responsive.widths', [320]);
    $media = Media::make(storedImage('page/photo.jpg'));

    expect($media->getSrcset(200))->toBe(implode(', ', [
        $media->getCropUrl(200) . ' 200w',
        $media->getCropUrl(320) . ' 320w',
        $media->getCropUrl(400) . ' 400w',
    ]));
});

it('lists the thumbnails converted to a format for a srcset', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.images.responsive.widths', []);
    $media = Media::make(storedImage('page/photo.jpg'));

    expect($media->getSrcset(200, 100, 'webp'))
        ->toStartWith('/storage/page/photo-200x100.jpg.webp?')
        ->toContain(', /storage/page/photo-400x200.jpg.webp?');
});

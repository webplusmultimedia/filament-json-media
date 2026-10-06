<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\Media;
use Illuminate\Support\Facades\Storage;

it('offers the thumbnails in several widths and in the configured formats to the browser', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.images.responsive.formats', ['webp']);
    $media = Media::make(storedImage('page/photo.jpg'));

    $html = (string) $this->blade('<x-gallery-json-media::responsive-image :media="$media" :width="600" :height="300" />', ['media' => $media]);

    expect($html)
        ->toContain('<picture>')
        ->toContain('<source type="image/webp" srcset="' . e($media->getSrcset(600, 300, 'webp')) . '" sizes="(max-width: 600px) 100vw, 600px">')
        ->toContain('srcset="' . e($media->getSrcset(600, 300)) . '"')
        ->toContain('sizes="(max-width: 600px) 100vw, 600px"');
});

it('uses the given sizes for the image and its sources', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.images.responsive.formats', ['webp']);
    $media = Media::make(storedImage('page/photo.jpg'));

    $html = (string) $this->blade('<x-gallery-json-media::responsive-image :media="$media" :width="600" sizes="50vw" />', ['media' => $media]);

    expect(substr_count($html, 'sizes="50vw"'))->toBe(2)
        ->and($html)->not->toContain('100vw');
});

it('offers the formats given to the component in their order', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg'));

    $html = (string) $this->blade('<x-gallery-json-media::responsive-image :media="$media" :width="600" :formats="[\'avif\', \'webp\']" />', ['media' => $media]);

    expect(strpos($html, 'type="image/avif"'))->toBeLessThan(strpos($html, 'type="image/webp"'))
        ->and($html)->toContain('photo-600x_.jpg.avif?');
});

it('renders a responsive image without picture when no format is configured', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.images.responsive.formats', []);
    $media = Media::make(storedImage('page/photo.jpg'));

    $html = (string) $this->blade('<x-gallery-json-media::responsive-image :media="$media" :width="600" />', ['media' => $media]);

    expect($html)->not->toContain('<picture>')
        ->toContain('srcset="' . e($media->getSrcset(600)) . '"');
});

it('renders the original svg without srcset nor picture', function () {
    Storage::fake('public');
    Storage::disk('public')->put('page/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
    $media = Media::make(['file' => 'page/logo.svg', 'disk' => 'public', 'mime_type' => 'image/svg+xml']);

    $html = (string) $this->blade('<x-gallery-json-media::responsive-image :media="$media" :width="600" />', ['media' => $media]);

    expect($html)->toContain('src="/storage/page/logo.svg"')
        ->not->toContain('srcset=')
        ->not->toContain('<picture>');
});

it('renders the original image without srcset nor picture when no width is given', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg'));

    $html = (string) $this->blade('<x-gallery-json-media::responsive-image :media="$media" />', ['media' => $media]);

    expect($html)->toContain('src="/storage/page/photo.jpg"')
        ->not->toContain('srcset=')
        ->not->toContain('<picture>');
});

it('gives the image the alt text of the media and the given attributes', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg', alt: 'Sunset'));

    $html = (string) $this->blade('<x-gallery-json-media::responsive-image :media="$media" :width="600" :height="300" class="rounded" />', ['media' => $media]);

    preg_match('/<img [^>]*>/', $html, $image);

    expect($image[0])
        ->toContain('src="' . e($media->getCropUrl(600, 300)) . '"')
        ->toContain('class="rounded"')
        ->toContain('alt="Sunset"')
        ->toContain('width="600"')
        ->toContain('height="300"')
        ->toContain('loading="lazy"')
        ->and(substr_count($html, 'class="rounded"'))->toBe(1);
});

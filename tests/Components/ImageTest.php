<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\Media;
use Illuminate\Support\Facades\Storage;

it('renders a lazy thumbnail of the requested size with the alt text of the media', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg', alt: 'Sunset'));

    $html = (string) $this->blade('<x-gallery-json-media::image :media="$media" :width="200" :height="150" />', ['media' => $media]);

    expect($html)
        ->toContain('src="' . e($media->getCropUrl(200, 150)) . '"')
        ->toContain('alt="Sunset"')
        ->toContain('width="200"')
        ->toContain('height="150"')
        ->toContain('loading="lazy"');
});

it('renders the original image without size when no size is given', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg'));

    $html = (string) $this->blade('<x-gallery-json-media::image :media="$media" />', ['media' => $media]);

    expect($html)
        ->toContain('src="/storage/page/photo.jpg"')
        ->not->toContain('width=')
        ->not->toContain('height=')
        ->not->toContain('srcset=')
        ->not->toContain('<picture>');
});

it('adds the given attributes to the image and lets them replace the defaults', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg', alt: 'Sunset'));

    $html = (string) $this->blade(
        '<x-gallery-json-media::image :media="$media" :width="200" class="rounded" alt="A red sunset" loading="eager" />',
        ['media' => $media],
    );

    expect($html)
        ->toContain('class="rounded"')
        ->toContain('alt="A red sunset"')
        ->toContain('loading="eager"')
        ->not->toContain('alt="Sunset"');
});

it('escapes the alt text of the media', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg', alt: '"><script>alert(1)</script>'));

    $html = (string) $this->blade('<x-gallery-json-media::image :media="$media" />', ['media' => $media]);

    expect($html)->not->toContain('<script>')
        ->toContain('alt="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"');
});

it('renders a single thumbnail without srcset nor picture when a width is given', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg'));

    $html = (string) $this->blade('<x-gallery-json-media::image :media="$media" :width="600" :height="300" />', ['media' => $media]);

    expect($html)->not->toContain('srcset=')
        ->not->toContain('<picture>');
});

it('renders the thumbnail converted to the given format', function () {
    Storage::fake('public');
    $media = Media::make(storedImage('page/photo.jpg'));

    $html = (string) $this->blade('<x-gallery-json-media::image :media="$media" :width="400" :height="300" format="webp" />', ['media' => $media]);

    expect($html)->toContain('src="' . e($media->getCropUrl(400, 300, format: 'webp')) . '"')
        ->toContain('/storage/page/photo-400x300.jpg.webp?')
        ->not->toContain('format=');
});

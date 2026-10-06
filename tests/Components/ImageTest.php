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
        ->not->toContain('height=');
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

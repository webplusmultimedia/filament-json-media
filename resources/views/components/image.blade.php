@props([
    'media',
    'width' => null,
    'height' => null,
])
@php
    /** @var \GalleryJsonMedia\JsonMedia\Media $media */
    // Without a size, the original is shown rather than a thumbnail of the same size
    $src = ($width || $height) ? $media->getCropUrl(width: $width, height: $height) : $media->getUrl();
@endphp
<img src="{{ $src }}" {{ $attributes->merge([
    'alt' => (string) $media->getCustomProperty('alt'),
    'width' => $width,
    'height' => $height,
    'loading' => 'lazy',
]) }}>

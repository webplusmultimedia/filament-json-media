@props([
    'media',
    'width' => null,
    'height' => null,
    'formats' => null,
])
@php
    /** @var \GalleryJsonMedia\JsonMedia\Media $media */
    $width = filled($width) ? (int) $width : null;
    $height = filled($height) ? (int) $height : null;
    // The browser chooses a thumbnail by its width : a svg or an image without width stays a single image
    $responsive = $width !== null && ! $media->isSvgFile();
    $sizes = $attributes->get('sizes', "(max-width: {$width}px) 100vw, {$width}px");
    $formats = $responsive ? ($formats ?? config('gallery-json-media.images.responsive.formats', ['webp'])) : [];
@endphp
@if ($formats)
<picture>
    @foreach ($formats as $format)
    <source type="image/{{ $format }}" srcset="{{ $media->getSrcset($width, $height, $format) }}" sizes="{{ $sizes }}">
    @endforeach
@endif
<x-gallery-json-media::image :media="$media" :width="$width" :height="$height" {{ $attributes->merge(['srcset' => $responsive ? $media->getSrcset($width, $height) : null, 'sizes' => $responsive ? $sizes : null]) }} />
@if ($formats)
</picture>
@endif

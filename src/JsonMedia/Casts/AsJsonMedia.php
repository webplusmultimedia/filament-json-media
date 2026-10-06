<?php

declare(strict_types=1);

namespace GalleryJsonMedia\JsonMedia\Casts;

use GalleryJsonMedia\JsonMedia\Document;
use GalleryJsonMedia\JsonMedia\Media;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Reads a json media field as a collection of Media (images) and Document (other files).
 *
 * @implements CastsAttributes<Collection<array-key, Media|Document>, iterable<array-key, mixed>|null>
 */
final class AsJsonMedia implements CastsAttributes
{
    /**
     * The medias are rebuilt on each read : a field that is only read is not saved again.
     */
    public bool $withoutObjectCaching = true;

    /**
     * @param  array<string, mixed>  $attributes
     * @return Collection<array-key, Media|Document>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Collection
    {
        $entries = is_string($value) ? json_decode($value, true) : null;

        return collect(is_array($entries) ? $entries : [])
            ->filter(fn (mixed $entry): bool => is_array($entry))
            ->map(fn (array $entry): Media | Document => Media::isImage(data_get($entry, 'mime_type', 'image/webp'))
                ? Media::make($entry)
                : Document::make($entry));
    }

    /**
     * @param  iterable<array-key, mixed>|null  $value
     * @param  array<string, mixed>  $attributes
     * @return array<string, string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        $entries = collect($value)
            ->map(fn (mixed $entry): mixed => $entry instanceof Arrayable ? $entry->toArray() : $entry)
            ->all();

        return [$key => json_encode($entries, JSON_THROW_ON_ERROR)];
    }
}

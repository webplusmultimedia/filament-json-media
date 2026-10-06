<?php

declare(strict_types=1);

namespace GalleryJsonMedia\JsonMedia\Concerns;

use Closure;
use GalleryJsonMedia\JsonMedia\Contracts\CanDeleteMedia;
use GalleryJsonMedia\JsonMedia\Contracts\HasMedia;
use GalleryJsonMedia\JsonMedia\Document;
use GalleryJsonMedia\JsonMedia\Media;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static deleting(Closure $param)
 */
// @phpstan-ignore trait.unused
trait InteractWithMedia
{
    protected static function bootInteractWithMedia(): void
    {
        static::deleting(function (HasMedia $model) {
            // A soft deleted model can be restored, so its files must be kept until it is force deleted
            if (in_array(SoftDeletes::class, class_uses_recursive($model)) && ! $model->isForceDeleting()) {
                return;
            }

            foreach ($model->getFieldsToDeleteMedia() as $field) {
                $model->deleteFilesFrom($field);
            }
        });
    }

    /**
     * @return Media[]
     */
    public function getMedias(string $fieldName): array
    {
        $medias = [];
        foreach ($this->getJsonMediaEntries($fieldName) as $image) {
            if (Media::isImage(data_get($image, 'mime_type', 'image/webp'))) {
                $medias[] = Media::make($image);
            }
        }

        return $medias;
    }

    /**
     * @return Media[]
     */
    public function getMediasWithoutFirst(string $fieldName): array
    {
        $medias = $this->getMedias($fieldName);
        array_shift($medias);

        return $medias;
    }

    /**
     * @return array<int,Document>
     */
    public function getDocuments(string $fieldName): array
    {
        $documents = [];
        foreach ($this->getJsonMediaEntries($fieldName) as $document) {
            if (! Media::isImage(data_get($document, 'mime_type', 'image/webp'))) {
                $documents[] = Document::make($document);
            }
        }

        return $documents;
    }

    /**
     * The json entries of a field, whether it is cast to an array, a collection or json medias.
     *
     * @return array<array-key, mixed>
     */
    private function getJsonMediaEntries(string $fieldName): array
    {
        return collect($this->{$fieldName})
            ->map(fn (mixed $entry): mixed => $entry instanceof Arrayable ? $entry->toArray() : $entry)
            ->all();
    }

    public function hasDocuments(string $fieldName): bool
    {
        return ! empty($this->getDocuments($fieldName));
    }

    public function getFirstMedia(string $fieldName): ?Media
    {
        return collect($this->getMedias($fieldName))->first();
    }

    public function getFirstMediaUrl(string $fieldName): ?string
    {
        if ($media = $this->getFirstMedia($fieldName)) {
            return $media->getUrl();
        }

        return null;
    }

    public function getFirstMediaCropUrl(string $fieldName, ?int $width = null, ?int $height = null, ?array $options = null, bool $withoutToken = false): ?string
    {
        if (! $firstMedia = $this->getFirstMedia($fieldName)) {
            return null;
        }

        return $firstMedia->getCropUrl($width, $height, $options, $withoutToken);
    }

    protected function getFieldsToDeleteMedia(): array
    {
        return [];
    }

    protected function deleteFilesFrom(string $field): void
    {
        /** @var CanDeleteMedia[] $medias */
        $medias = array_merge($this->getMedias($field), $this->getDocuments($field));
        foreach ($medias as $media) {
            $media->delete();
        }

    }

    public function mediasCount(string $field): int
    {
        return count($this->getMedias($field));
    }

    public function documentsCount(string $field): int
    {
        return count($this->getDocuments($field));
    }
}

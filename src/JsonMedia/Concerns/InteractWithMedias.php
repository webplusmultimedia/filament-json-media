<?php

namespace GalleryJsonMedia\JsonMedia\Concerns;

use Bkwld\Croppa\Facades\Croppa;
use GalleryJsonMedia\JsonMedia\Contracts\CanDeleteMedia;
use GalleryJsonMedia\JsonMedia\Contracts\HasMedia;
use GalleryJsonMedia\JsonMedia\Document;
use GalleryJsonMedia\JsonMedia\Media;

/**
 * @method static deleting(\Closure $param)
 */
trait InteractWithMedias
{
    protected static function InteractWithMedia(): void
    {
        static::deleting(function (HasMedia $model) {
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
        if (is_null($this->{$fieldName})) {
            return $medias;
        }
        foreach ($this->{$fieldName} as $image) {
            if (Media::isImage(data_get($image, 'mime_type'))) {
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
        if (is_null($this->{$fieldName})) {
            return [];
        }
        foreach ($this->{$fieldName} as $document) {
            if (! Media::isImage(data_get($document, 'mime_type'))) {
                $documents[] = Document::make($document);
            }
        }

        return $documents;
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

    public function getFirstMediaCropUrl(string $fieldName, ?int $width = null, ?int $height = null, ?array $options = null): ?string
    {
        if (! $firstMedia = $this->getFirstMediaUrl($fieldName)) {
            return null;
        }

        return url(Croppa::url($firstMedia, $width, $height, $options));
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
}

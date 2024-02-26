<?php

namespace WebplusMultimedia\GalleryJsonMedia\JsonMedia\Contracts;

use WebplusMultimedia\GalleryJsonMedia\JsonMedia\Media;

/**
 * @method void deleteFilesFrom(string $field)
 * @method array<int,string> getFieldsToDeleteMedia()
 */
interface HasMedia
{
    public function getMedias(string $fieldName): array;

    public function getFirstMedia(string $fieldName): ?Media;

    public function getMediasWithoutFirst(string $fieldName): array;

    public function getFirstMediaUrl(string $fieldName): ?string;

    public function getFirstMediaCropUrl(string $fieldName, ?int $width = null, ?int $height = null, ?array $options = null): ?string;

    public function getDocuments(string $fieldName): array;
}

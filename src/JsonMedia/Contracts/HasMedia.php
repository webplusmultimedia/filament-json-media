<?php

declare(strict_types=1);

namespace GalleryJsonMedia\JsonMedia\Contracts;

use GalleryJsonMedia\JsonMedia\Document;
use GalleryJsonMedia\JsonMedia\Media;

/**
 * @method void deleteFilesFrom(string $field)
 * @method array<int,string> getFieldsToDeleteMedia()
 */
interface HasMedia
{
    /**
     * @return Media[]
     */
    public function getMedias(string $fieldName): array;

    public function getFirstMedia(string $fieldName): ?Media;

    /**
     * @return Media[]
     */
    public function getMediasWithoutFirst(string $fieldName): array;

    public function getFirstMediaUrl(string $fieldName): ?string;

    public function getFirstMediaCropUrl(string $fieldName, ?int $width = null, ?int $height = null, ?array $options = null): ?string;

    /** @return Document[] */
    public function getDocuments(string $fieldName): array;

    public function mediasCount(string $field): int;
}

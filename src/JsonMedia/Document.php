<?php

declare(strict_types=1);

namespace GalleryJsonMedia\JsonMedia;

use GalleryJsonMedia\JsonMedia\Concerns\HasFile;
use GalleryJsonMedia\JsonMedia\Contracts\CanDeleteMedia;
use Stringable;

class Document implements CanDeleteMedia, Stringable
{
    use HasFile;

    public function __construct(
        protected array $content,
    ) {

    }

    public static function make(array $content): Document
    {
        return new self($content);
    }

    public function getUrl(): ?string
    {
        $disk = $this->getDisk();
        if ($fileName = $this->getFileName()) {
            return $disk->url($fileName);
        }

        return null;
    }

    public function getCustomProperty(string $property): mixed
    {
        return $this->getContentKeyValue('customProperties.' . $property);
    }

    public function delete(): void
    {
        if ($fileName = $this->getFileName()) {
            $this->getDisk()->delete($fileName);
        }
    }

    public function __toString(): string
    {
        return $this->getUrl() ?? '';
    }
}

<?php

namespace GalleryJsonMedia\JsonMedia;

use Bkwld\Croppa\Facades\Croppa;
use GalleryJsonMedia\JsonMedia\Contracts\CanDeleteMedia;
use Illuminate\Support\Facades\Storage;
use Stringable;

class Document implements CanDeleteMedia, Stringable
{
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
        if ($fileName = $this->getContentKeyValue('file')) {
            $disk = Storage::disk($this->getContentKeyValue('disk'));
            if ($disk->exists($fileName)) {
                return $disk->url($fileName);
            }

        }

        return null;
    }

    private function getContentKeyValue(string $key): mixed
    {
        return data_get($this->content, $key);
    }

    public function getCustomProperty(string $property): mixed
    {
        return $this->getContentKeyValue('customProperties.' . $property);
    }

    public function delete(): void
    {
        if ($this->getUrl()) {
            Croppa::delete($this->getUrl());
        }
    }

    public function __toString(): string
    {
        return $this->getUrl() ?? '';
    }
}

<?php

declare(strict_types=1);

namespace GalleryJsonMedia\JsonMedia\ImageManipulation;

use Illuminate\Contracts\Filesystem\Filesystem;
use Spatie\Image\Image;

final class Croppa
{
    public function __construct(protected Filesystem $filesystem, private string $filePath, private ?int $width = null, private ?int $height = null)
    {
    }

    public function url(): string
    {
        if (! $this->filesystem->exists($this->getPathNameForThumbs())) {
            $this->save();
        }

        return $this->filesystem->url($this->getPathNameForThumbs());
    }

    protected function save(): void
    {
        $image = Image::load($this->filesystem->path($this->filePath))
            ->quality(config('gallery-json-media.images.quality'))
            ->useImageDriver('imagick');

        if ($this->width and $this->height) {
            $image->crop(
                cropMethod: config('gallery-json-media.images.thumbnails-crop-method'),
                width: $this->width,
                height: $this->height
            );

        } else {
            if ($this->width) {
                $image->width($this->width);
            }
            if ($this->height) {
                $image->height($this->height);
            }
        }
        $image->save($this->filesystem->path($this->getPathNameForThumbs()));
    }

    protected function getPathNameForThumbs(): string
    {
        return $this->getBaseNameForTumbs() . $this->getSuffix() . '.' . $this->getFileInfo()['extension'];
    }

    protected function getBaseNameForTumbs()
    {
        $basePath = str($this->filePath)->beforeLast('/');

        return $basePath . '/' . $this->getFileInfo()['filename'];
    }

    protected function getSuffix(): string
    {
        $suffix = '-';
        if ($this->width === null && $this->height === null) {
            return '';
        }
        $first = $this->width ?? '_';
        $second = $this->height ?? '_';
        $suffix .= $first . 'x' . $second;

        return $suffix;
    }

    /**
     * @return array{dirname : string,basename:string,extension : string,filename : string}
     */
    protected function getFileInfo(): array
    {
        return pathinfo($this->filesystem->path($this->filePath));
    }

    public function reset(): void
    {
        //$this->filesystem->delete($this->filePath);
    }

    public function delete(): void
    {
    }
}

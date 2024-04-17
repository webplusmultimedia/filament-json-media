<?php

declare(strict_types=1);

namespace GalleryJsonMedia\JsonMedia\ImageManipulation;

use GalleryJsonMedia\JsonMedia\UrlParser;
use Illuminate\Contracts\Filesystem\Filesystem;
use Spatie\Image\Enums\CropPosition;
use Spatie\Image\Exceptions\InvalidImageDriver;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\Image\Image;

final class Croppa
{
    public function __construct(protected Filesystem $filesystem, private string $filePath, private ?int $width = null, private ?int $height = null)
    {
    }

    public function url(): string
    {
        /*if (! $this->filesystem->exists($this->getPathNameForThumbs())) {
            $this->save();
        }*/
        $url = $this->filesystem->url($this->getPathNameForThumbs());
        $url .= '?_token=' . UrlParser::make()->signingToken($url);

        return $url;
    }

    /**
     * @throws InvalidManipulation
     * @throws InvalidImageDriver
     */
    public function render(): void
    {
        $image = Image::useImageDriver(config('gallery-json-media.images.driver'))
            ->load($this->filesystem->path($this->filePath))
            ->quality(config('gallery-json-media.images.quality'));

        if ($this->width and $this->height) {
            $image->crop(
                position: config('gallery-json-media.images.thumbnails-crop-method', CropPosition::Center),
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
        $width = $this->width ?? '_';
        $height = $this->height ?? '_';
        $suffix .= $width . 'x' . $height;

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
        $search = $this->filesystem->path($this->getBaseNameForTumbs() . '-*.*');
        foreach (glob($search) as $file) {
            unlink($file);
        }
    }

    public function delete(): void
    {
        $this->reset();
        $this->filesystem->delete($this->filePath);
    }
}

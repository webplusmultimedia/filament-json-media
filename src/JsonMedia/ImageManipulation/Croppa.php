<?php

declare(strict_types=1);

namespace GalleryJsonMedia\JsonMedia\ImageManipulation;

use Exception;
use GalleryJsonMedia\JsonMedia\UrlParser;
use Illuminate\Contracts\Filesystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Exceptions\InvalidImageDriver;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\Image\Image;

final class Croppa
{
    public function __construct(protected Filesystem $storage, private string $filePath, private ?int $width = null, private ?int $height = null) {}

    public function url(): string
    {
        $url = $this->storage->url($this->getPathNameForThumbs());
        $url .= '?_token=' . UrlParser::make()->signingToken($url);

        // can be used for lazy rendering
        /*if (! $this->storage->exists($this->getPathNameForThumbs())) {
            defer(function () {
                $this->render();
            });
        }*/

        return $url;
    }

    /**
     * @throws InvalidManipulation
     * @throws InvalidImageDriver
     */
    public function render(): void
    {
        $image = Image::useImageDriver(config('gallery-json-media.images.driver'))
            ->load($this->storage->path($this->filePath))
            ->quality(config('gallery-json-media.images.quality'));

        try {

            if ($this->width && $this->height) {
                $image = $image->fit(
                    Fit::Crop,
                    desiredWidth: $this->width,
                    desiredHeight: $this->height
                );
            } else {
                if ($this->width) {
                    $image->width($this->width);
                }
                if ($this->height) {
                    $image->height($this->height);
                }
            }

            $image->save($this->getFullPathForThumb());
        } catch (InvalidManipulation $e) {
            throw new Exception('Invalid manipulation or you are need php 8.2');
        }
    }

    public function getFullPathForThumb(): string
    {
        return $this->storage->path($this->getPathNameForThumbs());
    }

    public function getPathNameForThumbs(): string
    {
        return $this->getBaseNameForTumbs() . $this->getSuffix() . '.' . $this->getFileInfo()['extension'];
    }

    protected function getBaseNameForTumbs(): string
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
        return pathinfo($this->storage->path($this->filePath));
    }

    public function reset(): void
    {
        $search = $this->storage->path($this->getBaseNameForTumbs() . '-*.*');
        foreach (glob($search) as $file) {
            unlink($file);
        }
    }

    public function delete(): void
    {
        $this->reset();
        $this->storage->delete($this->filePath);
    }

    public function cropsAreRemote(): bool
    {
        return ! $this->storage->getAdapter() instanceof LocalFilesystemAdapter;
    }
}

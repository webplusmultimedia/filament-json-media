<?php

declare(strict_types = 1);

namespace GalleryJsonMedia\JsonMedia\ImageManipulation;

use Illuminate\Contracts\Filesystem\Filesystem;
use Spatie\Image\Image;

final class Croppa
{
    public function __construct(protected Filesystem $filesystem, private ?string $filePath = NULL, private ?int $width = NULL, private ?int $height = NULL) {}

    public function url(): string
    {
        $this->save();

        return $this->filesystem->url($this->getPathName());
    }

    protected function save(): void
    {
        if (! $this->filesystem->exists($this->getPathName())) {
            $image = Image::load($this->filesystem->path($this->filePath));
            if ($this->width) {
                $image->width($this->width);
            }
            if ($this->height) {
                $image->height($this->height);
            }
            $image->save($this->filesystem->path($this->getPathName()));
        }
    }

    public function getPathName(): string
    {
        $basePath = str($this->filePath)->beforeLast('/');

        return $basePath . '/' . $this->getFileInfo()['filename'] . $this->getSuffix() . '.' . $this->getFileInfo()['extension'];
    }

    protected function getSuffix(): string
    {
        $suffix = '-';
        if ($this->width === NULL && $this->height === NULL) {
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
}

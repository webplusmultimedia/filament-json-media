<?php

declare(strict_types=1);

namespace GalleryJsonMedia\JsonMedia;

use Bkwld\Croppa\Facades\Croppa;
use Exception;
use GalleryJsonMedia\JsonMedia\Concerns\HasFile;
use GalleryJsonMedia\JsonMedia\Contracts\CanDeleteMedia;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\View;
use Stringable;

final class Media implements CanDeleteMedia, Htmlable, Stringable
{
    use HasFile;

    private string $svgMimeType = 'image/svg+xml';

    protected string $view = 'gallery-json-media::json-media.media';

    public function __construct(
        protected array $content,
        public int $width = 250,
        public int $height = 180,
    ) {
    }

    public static function make(array $content): Media
    {
        return new self($content);
    }

    public static function isImage(string $mimeType): bool
    {
        return str($mimeType)->startsWith('image');
    }

    public function getUrl(): ?string
    {
        if ($fileName = $this->getContentKeyValue('file')) {
            $storage = $this->getDisk();
            if ($storage->exists($fileName)) {
                return $storage->url($fileName);
            }
            //throw new Exception("File not Found [\$imagesDirectory] {$fileName}");
        }

        return null;
    }

    public function getCropUrl(?int $width = null, ?int $height = null, ?array $options = null): string
    {
        if ($this->isSvgFile()) {
            return $this->getUrl();
        }

        return url(Croppa::url($this->getUrl(), $width, $height, $options));
    }

    public function isSvgFile(): bool
    {
        return $this->getContentKeyValue('mime_type') === $this->svgMimeType;
    }

    public function getCustomProperty(string $property): mixed
    {
        return $this->getContentKeyValue('customProperties.' . $property);
    }

    public function withImageProperties(int $width, int $height): Media
    {
        $this->width = $width;
        $this->height = $height;

        return $this;
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

    public function render(): View
    {

        return view($this->getView(), ['media' => $this]);
    }

    /**
     * @throws \Throwable
     */
    public function toHtml(): string
    {
        return $this->render()->render();
    }

    public function withView(string $view): Media
    {
        $this->view = $view;

        return $this;
    }

    private function getView(): string
    {
        return $this->view;
    }
}

<?php

declare(strict_types=1);

namespace GalleryJsonMedia\JsonMedia;

use GalleryJsonMedia\JsonMedia\Concerns\HasFile;
use GalleryJsonMedia\JsonMedia\Contracts\CanDeleteMedia;
use GalleryJsonMedia\JsonMedia\ImageManipulation\Croppa;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\View;
use Stringable;
use Throwable;

/**
 * @implements Arrayable<string, mixed>
 */
final class Media implements Arrayable, CanDeleteMedia, Htmlable, Stringable
{
    use HasFile;

    private string $svgMimeType = 'image/svg+xml';

    protected string $view = 'gallery-json-media::json-media.media';

    public function __construct(
        protected array $content,
        public ?int $width = 250,
        public ?int $height = 180,
        public ?string $imgClass = null,
    ) {
        $this->getDisk();
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
        if ($fileName = $this->getFileName()) {
            return $this->getFileUrl($fileName);
        }

        return null;
    }

    public function getCropUrl(?int $width = null, ?int $height = null, ?array $options = null, bool $withoutToken = false): string
    {
        if ($this->isSvgFile()) {
            return $this->getUrl();
        }
        if ($fileName = $this->getFileName()) {
            return $this->getCroppa($fileName, $width, $height)->url($withoutToken);
        }

        return '';
    }

    private function getCroppa(string $fileName, ?int $width = null, ?int $height = null): Croppa
    {
        return new Croppa(
            $this->getDisk(),
            $fileName,
            $width,
            $height,
            $this->getContentKeyValue('disk'),
            $this->getVisibility(),
        );
    }

    public function isSvgFile(): bool
    {
        return $this->getContentKeyValue('mime_type') === $this->svgMimeType;
    }

    public function getCustomProperty(string $property): mixed
    {
        return $this->getContentKeyValue('customProperties.' . $property);
    }

    public function withImageProperties(?int $width = null, ?int $height = null, ?string $imgClass = null): Media
    {
        $this->width = $width;
        $this->height = $height;
        $this->imgClass = $imgClass;

        return $this;
    }

    public function delete(): void
    {
        if ($fileName = $this->getFileName()) {
            $this->getCroppa($fileName)->delete();
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
     * @throws Throwable
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

<?php

declare(strict_types=1);

namespace WebplusMultimedia\GalleryJsonMedia\Form\Concerns;

use Bkwld\Croppa\Facades\Croppa;
use Illuminate\Contracts\Routing\UrlGenerator;

trait HasExtensionFile
{
    protected static array $documentsExtension = ['doc', 'docx', 'pdf', 'xls', 'xlsx'];

    protected array $imagesExtension = ['jpg', 'jpge', 'png', 'svg', 'webp'];

    public static function isDocumentFile(string $file): bool
    {
        return in_array(static::getExtension($file), static::$documentsExtension);
    }

    public static function isSvgFile(string $file): bool
    {
        return static::getExtension($file) === 'svg';
    }

    protected static function getExtension(string $file): string
    {
        return str($file)->afterLast('.')->toString();
    }

    public function setDocumentsExtension(array $documentsExtension): static
    {
        static::$documentsExtension = $documentsExtension;

        return $this;
    }

    public function setImagesExtension(array $imagesExtension): static
    {
        $this->imagesExtension = $imagesExtension;

        return $this;
    }

    public function getDocumentsExtension(): array
    {
        return static::$documentsExtension;
    }

    public function getImagesExtension(): array
    {
        return $this->imagesExtension;
    }

    protected function getUrl(string $file, int $width = 320, int $height = 200): string | UrlGenerator
    {
        if ($isDocument = static::isDocumentFile($file) or static::isSvgFile($file)) {
            $url = url(str($file)->prepend('storage/')->toString()); // svg
            if ($isDocument) {
                $url = route(str(config('little-admin-architect.route.prefix'))->append('.documents.file')->toString(), ['document' => static::getExtension($file)]);
            }

            return $url;
        }

        return url(Croppa::url(str($file)->prepend('storage/')->toString(), $width, $height));

    }
}

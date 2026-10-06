<?php

declare(strict_types=1);

namespace GalleryJsonMedia\JsonMedia\ImageManipulation;

use Exception;
use GalleryJsonMedia\JsonMedia\UrlParser;
use GalleryJsonMedia\Support\Disk;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use LogicException;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Exceptions\InvalidImageDriver;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\Image\Image;

final class Croppa
{
    /**
     * The disk name is needed to build the thumbnail url of an image that is not public on a local disk.
     * The format converts the thumbnail (webp, avif...), its name keeps the extension of the image : photo-200x150.jpg.webp
     */
    public function __construct(
        protected Filesystem $storage,
        private string $filePath,
        private ?int $width = null,
        private ?int $height = null,
        private ?string $diskName = null,
        private string $visibility = 'public',
        private ?string $format = null,
    ) {}

    public function url(bool $withoutToken = false): string
    {
        if (! $this->servesThumbnailsLocally()) {
            return $this->signedRouteUrl();
        }

        $url = $this->storage->url($this->getPathNameForThumbs());
        if ($withoutToken) {
            // auto-generate thumb if not exist / can be used for lazy rendering
            if (! $this->storage->exists($this->getPathNameForThumbs())) {
                defer(function () {
                    $this->render();
                });
            }

            return $url;
        }
        // The disk url keeps its domain (CDN...), the route only signs its path, and the disk of the image when known
        $signedUrl = URL::signedRoute(
            'gallery-json-media.local-thumbnail',
            array_filter(['path' => UrlParser::make()->toPath($url), 'disk' => $this->diskName]),
            absolute: false,
        );

        return $url . '?' . parse_url($signedUrl, PHP_URL_QUERY);
    }

    /**
     * @throws InvalidManipulation
     * @throws InvalidImageDriver
     */
    public function render(): void
    {
        if (! $this->cropsAreRemote()) {
            $this->renderImage($this->storage->path($this->filePath), $this->getFullPathForThumb());

            return;
        }

        // spatie/image works on local paths : the remote image goes through temporary files, the target extension gives the format
        $source = $this->temporaryFilePath($this->getFileInfo()['extension']);
        $target = $this->temporaryFilePath(pathinfo($this->getPathNameForThumbs(), PATHINFO_EXTENSION));

        try {
            $sourceStream = $this->storage->readStream($this->filePath);
            file_put_contents($source, $sourceStream);
            fclose($sourceStream);

            $this->renderImage($source, $target);

            $targetStream = fopen($target, 'r');
            $this->storage->put($this->getPathNameForThumbs(), $targetStream);
            fclose($targetStream);
        } finally {
            foreach ([$source, $target] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        // Buckets without ACL refuse to change the visibility, their policy decides
        rescue(fn () => $this->storage->setVisibility($this->getPathNameForThumbs(), $this->visibility), report: false);
    }

    public function getFullPathForThumb(): string
    {
        return $this->storage->path($this->getPathNameForThumbs());
    }

    public function getPathNameForThumbs(): string
    {
        $extension = $this->getFileInfo()['extension'];
        $path = $this->getBaseNameForTumbs() . $this->getSuffix() . '.' . $extension;

        return $this->isConverted() ? $path . '.' . $this->format : $path;
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
        return pathinfo($this->filePath);
    }

    public function reset(): void
    {
        ['dirname' => $directory, 'filename' => $filename, 'extension' => $extension] = $this->getFileInfo();
        // Other images can share the name prefix (photo-2.jpg for photo.jpg), so keep only "{name}-{width}x{height}.{ext}(.{format})"
        $thumbPattern = '/^' . preg_quote($filename, '/') . '-[0-9_]+x[0-9_]+\.' . preg_quote($extension, '/') . '(\.(' . UrlParser::EXTENSIONS . '))?$/';

        $thumbnails = array_values(array_filter(
            $this->storage->files($directory === '.' ? '' : $directory),
            fn (string $file): bool => (bool) preg_match($thumbPattern, basename($file)),
        ));

        if ($thumbnails !== []) {
            $this->storage->delete($thumbnails);
        }
    }

    public function delete(): void
    {
        $this->reset();
        $this->storage->delete($this->filePath);
    }

    public function cropsAreRemote(): bool
    {
        return ! Disk::isLocal($this->storage);
    }

    /**
     * Only a public image on a local disk is served by the web server, with the token fallback route.
     */
    private function servesThumbnailsLocally(): bool
    {
        return ! $this->cropsAreRemote() && $this->visibility === 'public';
    }

    /**
     * A format that is the one of the image does not convert it.
     */
    private function isConverted(): bool
    {
        return $this->format !== null && strcasecmp($this->format, $this->getFileInfo()['extension']) !== 0;
    }

    private function signedRouteUrl(): string
    {
        if ($this->diskName === null) {
            throw new LogicException('The disk name is needed to build the thumbnail url of a remote or private image.');
        }

        $parameters = [
            'disk' => $this->diskName,
            'path' => $this->getPathNameForThumbs(),
            'visibility' => $this->visibility,
        ];

        // A link to a private file must not outlive its temporary url, even when it is cached in a page
        if ($this->visibility === 'private') {
            return URL::temporarySignedRoute('gallery-json-media.thumbnail', now()->addMinutes(Disk::temporaryUrlTtl()), $parameters);
        }

        return URL::signedRoute('gallery-json-media.thumbnail', $parameters);
    }

    /**
     * @throws InvalidManipulation
     * @throws InvalidImageDriver
     */
    private function renderImage(string $source, string $target): void
    {
        $image = Image::useImageDriver(config('gallery-json-media.images.driver'))
            ->load($source)
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

            $image->save($target);
        } catch (InvalidManipulation $e) {
            throw new Exception('Invalid manipulation or you are need php 8.2');
        }
    }

    private function temporaryFilePath(string $extension): string
    {
        return sys_get_temp_dir() . '/gallery-json-media-' . Str::random(32) . '.' . $extension;
    }
}

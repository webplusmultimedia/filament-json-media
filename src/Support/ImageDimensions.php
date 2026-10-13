<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Support;

use Imagick;
use InvalidArgumentException;
use Throwable;

/**
 * The size of an image as its thumbnails see it : the drivers turn it by its exif orientation.
 */
final readonly class ImageDimensions
{
    public function __construct(
        public int $width,
        public int $height,
    ) {
        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('An image is at least one pixel wide and high.');
        }
    }

    /**
     * Only the header of the image is read, its pixels are not decoded.
     */
    public static function fromPath(string $path): ?self
    {
        $size = @getimagesize($path);

        // Some avif are read without their size
        if ($size === false || $size[0] < 1 || $size[1] < 1) {
            return null;
        }

        // The orientations 5 to 8 are a quarter turn : the sides of the image swap
        return in_array(self::orientation($path, $size[2]), [5, 6, 7, 8], true)
            ? new self($size[1], $size[0])
            : new self($size[0], $size[1]);
    }

    /**
     * A remote image or a temporary upload is copied by chunks to a temporary file, as getimagesize() reads a path.
     *
     * @param  resource|null  $stream  read to its end and closed
     */
    public static function fromStream($stream): ?self
    {
        if (! is_resource($stream)) {
            return null;
        }

        $file = tmpfile();

        try {
            stream_copy_to_stream($stream, $file);

            return self::fromPath(stream_get_meta_data($file)['uri']);
        } finally {
            fclose($file);
            fclose($stream);
        }
    }

    /**
     * The size recorded in a json media entry, on upload or by gallery-json-media:regenerate.
     *
     * @param  array<array-key, mixed>  $entry
     */
    public static function fromEntry(array $entry): ?self
    {
        $width = $entry['width'] ?? null;
        $height = $entry['height'] ?? null;

        return is_int($width) && is_int($height) && $width > 0 && $height > 0 ? new self($width, $height) : null;
    }

    /**
     * The widest thumbnail that does not enlarge the image : a cropped one, of the ratio width x height,
     * also keeps its height within the image.
     */
    public function maxWidthFor(int $width, ?int $height = null): int
    {
        return $height ? max(1, min($this->width, intdiv($this->height * $width, $height))) : $this->width;
    }

    /**
     * @return array{width : int, height : int}
     */
    public function toArray(): array
    {
        return ['width' => $this->width, 'height' => $this->height];
    }

    /**
     * The exif orientation, read as the drivers read it : by the exif extension, or by Imagick without decoding the image.
     */
    private static function orientation(string $path, int $type): int
    {
        if (function_exists('exif_read_data')) {
            $exif = in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM], true) ? @exif_read_data($path) : false;

            return is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        }

        if (class_exists(Imagick::class)) {
            try {
                $image = new Imagick;
                $image->pingImage($path);

                return $image->getImageOrientation();
            } catch (Throwable) {
                return 1;
            }
        }

        return 1;
    }
}

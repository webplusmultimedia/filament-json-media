<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 *
 * @category    Category
 *
 * @author      daniel
 *
 * @link        http://webplusm.net
 * Date: 16/04/2024 12:04
 */

namespace GalleryJsonMedia\JsonMedia;

use Exception;

final class UrlParser
{
    public static function make(): UrlParser
    {
        return new self;
    }

    /**
     * The pattern used to indetify a request path as a Croppa-style URL
     * https://github.com/BKWLD/croppa/wiki/Croppa-regex-pattern.
     * A thumbnail converted to another format keeps the extension of its source image : photo-200x150.jpg.webp
     *
     * @return string
     */
    public const PATTERN = '(.+)-([0-9_]+)x([0-9_]+)(-[0-9a-zA-Z(),\-._]+)*(?:\.(' . self::EXTENSIONS . '))?\.(' . self::EXTENSIONS . ')$';

    public const EXTENSIONS = 'jpg|jpeg|png|gif|webp|avif|JPG|JPEG|PNG|GIF|WEBP|AVIF';

    public function routePattern(): string
    {
        return sprintf('(?=%s)(?=%s).+', config('gallery-json-media.images.path'), self::PATTERN);
    }

    /**
     * Parse a request path into Croppa instructions.
     *
     *
     * @return array{path : string,width : int|null,height : int|null,options : null|string,format : null|string}|false
     *
     * @throws Exception
     */
    public function parse(string $request): array | false
    {
        if (! $thumbnail = $this->matchThumbnail('#' . self::PATTERN . '#', $request)) {
            return false;
        }

        return [
            ...$thumbnail,
            'path' => $this->relativePath($thumbnail['path']),
        ];
    }

    /**
     * Parse a thumbnail path relative to its disk, as used by the remote thumbnail route.
     *
     * @return array{path : string,width : int|null,height : int|null,format : null|string}|false
     */
    public function parseThumbnailPath(string $path): array | false
    {
        if (! $thumbnail = $this->matchThumbnail('#^' . self::PATTERN . '#', $path)) {
            return false;
        }
        unset($thumbnail['options']);

        return $thumbnail;
    }

    /**
     * @return array{path : string,width : int|null,height : int|null,options : null|string,format : null|string}|false
     */
    private function matchThumbnail(string $pattern, string $subject): array | false
    {
        if (! preg_match($pattern, $subject, $matches)) {
            return false;
        }
        // Without a source extension, the thumbnail has the format of its image
        $converted = $matches[5] !== '';

        return [
            'path' => $matches[1] . '.' . ($converted ? $matches[5] : $matches[6]),
            'width' => $matches[2] === '_' ? null : (int) $matches[2],
            'height' => $matches[3] === '_' ? null : (int) $matches[3],
            'options' => $matches[4],
            'format' => $converted ? $matches[6] : null,
        ];
    }

    /**
     * Extract the path from a URL and remove it's leading slash.
     */
    public function toPath(string $url): string
    {
        return ltrim(parse_url($url, PHP_URL_PATH), '/');
    }

    /**
     * Take a URL or path to an image and get the path relative to the src and
     * crops dirs by using the `path` config regex.
     */
    public function relativePath(string $url): string
    {
        $path = $this->toPath($url);
        $configPath = config('gallery-json-media.images.path');
        if (! preg_match('#' . $configPath . '#', $path, $matches)) {
            throw new Exception("{$url} doesn't match `{$configPath}`");
        }

        return $matches[1];
    }
}

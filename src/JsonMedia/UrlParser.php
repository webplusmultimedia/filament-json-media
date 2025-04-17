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
     *
     * @return string
     */
    public const PATTERN = '(.+)-([0-9_]+)x([0-9_]+)(-[0-9a-zA-Z(),\-._]+)*\.(jpg|jpeg|png|gif|webp|avif|JPG|JPEG|PNG|GIF|WEBP|AVIF)$';

    /**
     * Generate the signing token from a URL or path.
     * Or, if no key was defined, return nothing.
     */
    public function signingToken(string $url): ?string
    {
        $signing_key = config('gallery-json-media.images.signing_key');
        $key = config($signing_key);
        if ($key) {
            return md5($key . basename($url));
        }

        return null;
    }

    public function routePattern(): string
    {
        return sprintf('(?=%s)(?=%s).+', config('gallery-json-media.images.path'), self::PATTERN);
    }

    /**
     * Parse a request path into Croppa instructions.
     *
     *
     * @return array{path : string,width : int|null,height : int|null,options : null|string}|false
     *
     * @throws Exception
     */
    public function parse(string $request): array | false
    {
        if (! preg_match('#' . self::PATTERN . '#', $request, $matches)) {
            return false;
        }

        return [
            'path' => $this->relativePath($matches[1] . '.' . $matches[5]), // Path
            'width' => $matches[2] === '_' ? null : (int) $matches[2],    // Width
            'height' => $matches[3] === '_' ? null : (int) $matches[3],    // Height
            'options' => $matches[4],                      // Options
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

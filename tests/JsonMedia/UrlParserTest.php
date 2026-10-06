<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\UrlParser;

it('parses the source path and the dimensions of a thumbnail path', function (string $request, string $path, ?int $width, ?int $height) {
    $parsed = UrlParser::make()->parse($request);

    expect($parsed)->toMatchArray([
        'path' => $path,
        'width' => $width,
        'height' => $height,
    ]);
})->with([
    'width and height' => ['storage/web_attachments/page/photo-200x150.jpg', 'web_attachments/page/photo.jpg', 200, 150],
    'width only' => ['storage/web_attachments/page/photo-200x_.jpg', 'web_attachments/page/photo.jpg', 200, null],
    'height only' => ['storage/web_attachments/page/photo-_x150.jpg', 'web_attachments/page/photo.jpg', null, 150],
    'full url' => ['http://localhost/storage/page/my-photo-200x150.webp', 'page/my-photo.webp', 200, 150],
]);

it('does not parse a path without dimensions', function () {
    expect(UrlParser::make()->parse('storage/web_attachments/page/photo.jpg'))->toBeFalse();
});

it('rejects a thumbnail path outside of the configured storage path', function () {
    UrlParser::make()->parse('uploads/page/photo-200x150.jpg');
})->throws(Exception::class, "uploads/page/photo.jpg doesn't match `storage/(.*)$`");

it('parses the format of a converted thumbnail and keeps the path of its source image', function (string $request, string $path, ?string $format) {
    expect(UrlParser::make()->parse($request))->toMatchArray(['path' => $path, 'width' => 200, 'height' => 150, 'format' => $format]);
})->with([
    'converted to webp' => ['storage/page/photo-200x150.jpg.webp', 'page/photo.jpg', 'webp'],
    'converted to avif' => ['storage/page/photo-200x150.PNG.avif', 'page/photo.PNG', 'avif'],
    'not converted' => ['storage/page/photo-200x150.webp', 'page/photo.webp', null],
]);

it('parses the format of a converted thumbnail path relative to its disk', function () {
    expect(UrlParser::make()->parseThumbnailPath('page/photo-200x_.jpg.webp'))
        ->toBe(['path' => 'page/photo.jpg', 'width' => 200, 'height' => null, 'format' => 'webp']);
});

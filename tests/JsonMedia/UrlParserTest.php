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

it('signs a thumbnail with the application key and its file name', function () {
    config()->set('app.key', 'test-signing-key');

    $token = UrlParser::make()->signingToken('/storage/web_attachments/page/photo-200x150.jpg');

    expect($token)->toBe('5629edb7d2a8bd48f9423cb37874eb8f');
});

it('signs each thumbnail size with a different token', function () {
    config()->set('app.key', 'test-signing-key');
    $parser = UrlParser::make();

    expect($parser->signingToken('page/photo-200x150.jpg'))
        ->not->toBe($parser->signingToken('page/photo-300x230.jpg'));
});

it('returns no token when no signing key is configured', function () {
    config()->set('app.key', null);

    expect(UrlParser::make()->signingToken('page/photo-200x150.jpg'))->toBeNull();
});

<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\ImageManipulation\Croppa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

it('crops the image to the exact size when a width and a height are given', function () {
    Storage::fake('public');
    storedImage('page/photo.jpg', 800, 600);

    (new Croppa(Storage::disk('public'), 'page/photo.jpg', 200, 200))->render();

    expect(array_slice(getimagesize(Storage::disk('public')->path('page/photo-200x200.jpg')), 0, 2))
        ->toBe([200, 200]);
});

it('keeps the ratio when only one dimension is given', function (?int $width, ?int $height, string $thumbnail, array $size) {
    Storage::fake('public');
    storedImage('page/photo.jpg', 800, 600);

    (new Croppa(Storage::disk('public'), 'page/photo.jpg', $width, $height))->render();

    expect(array_slice(getimagesize(Storage::disk('public')->path($thumbnail)), 0, 2))->toBe($size);
})->with([
    'width only' => [200, null, 'page/photo-200x_.jpg', [200, 150]],
    'height only' => [null, 150, 'page/photo-_x150.jpg', [200, 150]],
]);

it('returns a thumbnail url signed by Laravel for its path', function () {
    Storage::fake('public');
    storedImage('page/photo.jpg');

    $url = (new Croppa(Storage::disk('public'), 'page/photo.jpg', 200, 150))->url();

    expect($url)
        ->toStartWith('/storage/page/photo-200x150.jpg?signature=')
        ->and(URL::hasValidSignature(Request::create($url), absolute: false))->toBeTrue();
});

it('generates the missing thumbnail when an unsigned url is requested', function () {
    Storage::fake('public');
    $this->withoutDefer();
    storedImage('page/photo.jpg');

    $url = (new Croppa(Storage::disk('public'), 'page/photo.jpg', 200, 150))->url(withoutToken: true);

    expect($url)->toBe('/storage/page/photo-200x150.jpg');
    Storage::disk('public')->assertExists('page/photo-200x150.jpg');
});

it('removes the thumbnails and keeps the original on reset', function () {
    Storage::fake('public');
    storedImage('page/photo.jpg');
    $croppa = new Croppa(Storage::disk('public'), 'page/photo.jpg', 200, 150);
    $croppa->render();

    $croppa->reset();

    Storage::disk('public')->assertExists('page/photo.jpg');
    Storage::disk('public')->assertMissing('page/photo-200x150.jpg');
});

it('keeps the other images whose name starts like the original on reset', function () {
    Storage::fake('public');
    storedImage('page/chat.jpg');
    storedImage('page/chat-noir.jpg');
    storedImage('page/chat-200x150.png');
    $croppa = new Croppa(Storage::disk('public'), 'page/chat.jpg', 200, 150);
    $croppa->render();

    $croppa->reset();

    Storage::disk('public')->assertMissing('page/chat-200x150.jpg');
    Storage::disk('public')->assertExists(['page/chat.jpg', 'page/chat-noir.jpg', 'page/chat-200x150.png']);
});

it('removes the original and its thumbnails on delete', function () {
    Storage::fake('public');
    storedImage('page/photo.jpg');
    $croppa = new Croppa(Storage::disk('public'), 'page/photo.jpg', 200, 150);
    $croppa->render();

    $croppa->delete();

    Storage::disk('public')->assertMissing(['page/photo.jpg', 'page/photo-200x150.jpg']);
});

it('creates the thumbnail on a remote disk', function () {
    $disk = fakeRemoteDisk();
    storedImage('page/photo.jpg', 800, 600, disk: 's3');

    (new Croppa($disk, 'page/photo.jpg', 200, 150, diskName: 's3'))->render();

    expect(array_slice(getimagesizefromstring($disk->get('page/photo-200x150.jpg')), 0, 2))->toBe([200, 150]);
});

it('gives the thumbnail the visibility of its image', function (string $visibility) {
    $disk = fakeRemoteDisk();
    storedImage('page/photo.jpg', disk: 's3');

    (new Croppa($disk, 'page/photo.jpg', 200, 150, diskName: 's3', visibility: $visibility))->render();

    expect($disk->getVisibility('page/photo-200x150.jpg'))->toBe($visibility);
})->with(['public', 'private']);

it('removes the thumbnails of a remote image and keeps its neighbours', function () {
    $disk = fakeRemoteDisk();
    storedImage('page/chat.jpg', disk: 's3');
    storedImage('page/chat-noir.jpg', disk: 's3');
    $croppa = new Croppa($disk, 'page/chat.jpg', 200, 150, diskName: 's3');
    $croppa->render();

    $croppa->delete();

    $disk->assertMissing(['page/chat.jpg', 'page/chat-200x150.jpg']);
    $disk->assertExists('page/chat-noir.jpg');
});

it('returns a signed app url without expiration for a public remote thumbnail', function () {
    $disk = fakeRemoteDisk();
    storedImage('page/photo.jpg', disk: 's3');

    $url = (new Croppa($disk, 'page/photo.jpg', 200, 150, diskName: 's3'))->url();

    expect($url)
        ->toStartWith('http://localhost/gallery-json-media/thumbnails/s3/page/photo-200x150.jpg?')
        ->not->toContain('expires=')
        ->and(URL::hasValidSignature(Request::create($url)))->toBeTrue();
});

it('returns a signed app url that expires with the temporary urls for a private thumbnail', function () {
    $this->travelTo('2026-01-01 00:00:00');
    $disk = fakeRemoteDisk();
    storedImage('page/photo.jpg', disk: 's3', visibility: 'private');

    $url = (new Croppa($disk, 'page/photo.jpg', 200, 150, diskName: 's3', visibility: 'private'))->url();

    expect($url)
        ->toStartWith('http://localhost/gallery-json-media/thumbnails/s3/page/photo-200x150.jpg?')
        ->toContain('expires=1767225900')
        ->and(URL::hasValidSignature(Request::create($url)))->toBeTrue();
});

it('returns a signed app url for a private image on a local disk', function () {
    Storage::fake('local');
    storedImage('page/photo.jpg', disk: 'local', visibility: 'private');

    $url = (new Croppa(Storage::disk('local'), 'page/photo.jpg', 200, 150, diskName: 'local', visibility: 'private'))->url();

    expect($url)->toStartWith('http://localhost/gallery-json-media/thumbnails/local/page/photo-200x150.jpg?');
});

it('converts the thumbnail to the requested format under the name of its source image', function () {
    Storage::fake('public');
    storedImage('page/photo.jpg', 800, 600);

    (new Croppa(Storage::disk('public'), 'page/photo.jpg', 200, 150, format: 'webp'))->render();

    $size = getimagesize(Storage::disk('public')->path('page/photo-200x150.jpg.webp'));
    expect([$size[0], $size[1], $size['mime']])->toBe([200, 150, 'image/webp']);
});

it('keeps the name of a thumbnail requested in the format of its image', function () {
    Storage::fake('public');
    storedImage('page/photo.jpg');

    $croppa = new Croppa(Storage::disk('public'), 'page/photo.jpg', 200, 150, format: 'jpg');

    expect($croppa->getPathNameForThumbs())->toBe('page/photo-200x150.jpg');
});

it('converts the thumbnail of a remote image to the requested format', function () {
    $disk = fakeRemoteDisk();
    storedImage('page/photo.jpg', 800, 600, disk: 's3');

    (new Croppa($disk, 'page/photo.jpg', 200, 150, diskName: 's3', format: 'webp'))->render();

    expect(getimagesizefromstring($disk->get('page/photo-200x150.jpg.webp'))['mime'])->toBe('image/webp');
});

it('removes the converted thumbnails on reset', function () {
    Storage::fake('public');
    storedImage('page/photo.jpg');
    (new Croppa(Storage::disk('public'), 'page/photo.jpg', 200, 150, format: 'webp'))->render();

    (new Croppa(Storage::disk('public'), 'page/photo.jpg'))->reset();

    Storage::disk('public')->assertMissing('page/photo-200x150.jpg.webp');
    Storage::disk('public')->assertExists('page/photo.jpg');
});

it('keeps the converted thumbnails of the other images on reset', function () {
    Storage::fake('public');
    storedImage('page/chat.jpg');
    storedImage('page/chat-noir.jpg');
    storedImage('page/chat.png');
    Storage::disk('public')->put('page/chat-200x150.jpg.webp', 'thumbnail');
    Storage::disk('public')->put('page/chat-noir-200x150.jpg.webp', 'thumbnail of a neighbour');
    Storage::disk('public')->put('page/chat-200x150.png.webp', 'thumbnail of another format');

    (new Croppa(Storage::disk('public'), 'page/chat.jpg'))->reset();

    Storage::disk('public')->assertMissing('page/chat-200x150.jpg.webp');
    Storage::disk('public')->assertExists(['page/chat-noir-200x150.jpg.webp', 'page/chat-200x150.png.webp']);
});

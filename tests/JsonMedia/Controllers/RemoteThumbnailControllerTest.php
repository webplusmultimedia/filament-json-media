<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\Media;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

it('generates the missing thumbnail and redirects to its public url', function () {
    $disk = fakeRemoteDisk();
    $url = Media::make(storedImage('page/photo.jpg', 800, 600, disk: 's3'))->getCropUrl(200, 150);

    $response = $this->get($url);

    $response->assertRedirect('https://bucket.test/page/photo-200x150.jpg');
    expect(array_slice(getimagesizefromstring($disk->get('page/photo-200x150.jpg')), 0, 2))->toBe([200, 150]);
});

it('redirects to a temporary url for a private image', function () {
    $this->travelTo('2026-01-01 00:00:00');
    fakeRemoteDisk();
    $url = Media::make(storedImage('page/photo.jpg', disk: 's3', visibility: 'private'))->getCropUrl(200, 150);

    $this->get($url)->assertRedirect('https://bucket.test/page/photo-200x150.jpg?expires=1767225900');
});

it('does not generate again an existing thumbnail', function () {
    $disk = fakeRemoteDisk();
    $url = Media::make(storedImage('page/photo.jpg', disk: 's3'))->getCropUrl(200, 150);
    $disk->put('page/photo-200x150.jpg', 'existing thumbnail');

    $this->get($url)->assertRedirect('https://bucket.test/page/photo-200x150.jpg');

    expect($disk->get('page/photo-200x150.jpg'))->toBe('existing thumbnail');
});

it('returns 403 and generates nothing when the url was tampered with', function () {
    $disk = fakeRemoteDisk();
    $url = Media::make(storedImage('page/photo.jpg', disk: 's3'))->getCropUrl(200, 150);

    $this->get(str_replace('photo-200x150.jpg', 'photo-300x150.jpg', $url))->assertForbidden();

    $disk->assertMissing(['page/photo-200x150.jpg', 'page/photo-300x150.jpg']);
});

it('returns 403 when the url of a private thumbnail has expired', function () {
    $this->travelTo('2026-01-01 00:00:00');
    $disk = fakeRemoteDisk();
    $url = Media::make(storedImage('page/photo.jpg', disk: 's3', visibility: 'private'))->getCropUrl(200, 150);
    $this->travel(6)->minutes();

    $this->get($url)->assertForbidden();

    $disk->assertMissing('page/photo-200x150.jpg');
});

it('returns 404 when the image is missing from the disk', function () {
    $disk = fakeRemoteDisk();
    $url = Media::make(storedImage('page/photo.jpg', disk: 's3'))->getCropUrl(200, 150);
    $disk->delete('page/photo.jpg');

    $this->get($url)->assertNotFound();
});

it('returns 404 for a path without dimensions', function () {
    fakeRemoteDisk();
    storedImage('page/photo.jpg', disk: 's3');

    $this->get(URL::signedRoute('gallery-json-media.thumbnail', ['disk' => 's3', 'path' => 'page/photo.jpg']))
        ->assertNotFound();
});

it('reports the failure and redirects to the original image when the thumbnail cannot be generated', function () {
    Exceptions::fake();
    $disk = fakeRemoteDisk();
    $disk->put('page/photo.jpg', 'not an image');
    $url = Media::make(['file' => 'page/photo.jpg', 'disk' => 's3', 'mime_type' => 'image/jpeg'])->getCropUrl(200, 150);

    $this->get($url)->assertRedirect('https://bucket.test/page/photo.jpg');

    Exceptions::assertReportedCount(1);
    $disk->assertMissing('page/photo-200x150.jpg');
});

it('serves itself the thumbnail of a private image stored on a local disk', function () {
    // Laravel serves the local disk on /storage, where the token route of the public thumbnails would catch a redirection
    Storage::fake('local');
    $url = Media::make(storedImage('page/photo.jpg', 800, 600, disk: 'local', visibility: 'private'))->getCropUrl(200, 150);

    $response = $this->get($url);

    $response->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    expect(array_slice(getimagesizefromstring($response->streamedContent()), 0, 2))->toBe([200, 150]);
});

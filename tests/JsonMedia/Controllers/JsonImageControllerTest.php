<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\Controllers\JsonImageController;
use GalleryJsonMedia\JsonMedia\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

it('generates the requested thumbnail and redirects to it', function () {
    Storage::fake('public');
    $url = Media::make(storedImage('web_attachments/page/photo.jpg', 800, 600))->getCropUrl(200, 150);

    $response = $this->get($url);

    $response->assertRedirect('/storage/web_attachments/page/photo-200x150.jpg');
    expect(array_slice(getimagesize(Storage::disk('public')->path('web_attachments/page/photo-200x150.jpg')), 0, 2))
        ->toBe([200, 150]);
});

it('returns 403 and generates nothing when the signature is not valid', function (string $pattern, string $replacement) {
    Storage::fake('public');
    $url = Media::make(storedImage('web_attachments/page/photo.jpg'))->getCropUrl(200, 150);

    $this->get(preg_replace($pattern, $replacement, $url))->assertForbidden();

    Storage::disk('public')->assertMissing(['web_attachments/page/photo-200x150.jpg', 'web_attachments/page/photo-300x150.jpg']);
})->with([
    'missing signature' => ['/&signature=.*$/', ''],
    'signature of another size' => ['/photo-200x150/', 'photo-300x150'],
    'signature of another disk' => ['/disk=public/', 'disk=local'],
    'former md5 token' => ['/\?.*$/', '?_token=5a309cfd8a927af6f2c418bcd131c487'],
]);

it('does not route a path without dimensions to the thumbnail controller', function () {
    // Laravel may answer itself on /storage (its served local disk), so only the matched route is the package contract
    $route = rescue(
        fn () => Route::getRoutes()->match(Request::create('/storage/web_attachments/page/photo.jpg')),
        report: false,
    );

    expect($route?->getActionName())->not->toBe(JsonImageController::class . '@handle');
});

it('generates the thumbnail on the disk of its image, whatever the default disk', function () {
    fakeRemoteDisk();
    config()->set('gallery-json-media.disk', 's3');
    Storage::fake('public');
    $url = Media::make(storedImage('web_attachments/page/photo.jpg', 800, 600))->getCropUrl(200, 150);

    $this->get($url)->assertRedirect('/storage/web_attachments/page/photo-200x150.jpg');

    Storage::disk('public')->assertExists('web_attachments/page/photo-200x150.jpg');
});

it('returns 404 when the image is missing from the disk', function () {
    Storage::fake('public');
    $url = Media::make(storedImage('web_attachments/page/photo.jpg'))->getCropUrl(200, 150);
    Storage::disk('public')->delete('web_attachments/page/photo.jpg');

    $this->get($url)->assertNotFound();
});

it('generates a thumbnail converted to the requested format and redirects to it', function () {
    Storage::fake('public');
    $url = Media::make(storedImage('web_attachments/page/photo.jpg', 800, 600))->getCropUrl(200, 150, format: 'webp');

    $this->get($url)->assertRedirect('/storage/web_attachments/page/photo-200x150.jpg.webp');

    expect(getimagesize(Storage::disk('public')->path('web_attachments/page/photo-200x150.jpg.webp'))['mime'])->toBe('image/webp');
});

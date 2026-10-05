<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\Controllers\JsonImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

it('generates the requested thumbnail and redirects to it', function () {
    Storage::fake('public');
    storedImage('web_attachments/page/photo.jpg', 800, 600);

    $response = $this->get('/storage/web_attachments/page/photo-200x150.jpg?_token=5a309cfd8a927af6f2c418bcd131c487');

    $response->assertRedirect('/storage/web_attachments/page/photo-200x150.jpg');
    expect(array_slice(getimagesize(Storage::disk('public')->path('web_attachments/page/photo-200x150.jpg')), 0, 2))
        ->toBe([200, 150]);
});

it('returns 404 and generates nothing when the token is invalid', function (?string $token) {
    Storage::fake('public');
    storedImage('web_attachments/page/photo.jpg');

    $response = $this->get('/storage/web_attachments/page/photo-200x150.jpg' . ($token === null ? '' : "?_token={$token}"));

    $response->assertNotFound();
    Storage::disk('public')->assertMissing('web_attachments/page/photo-200x150.jpg');
})->with([
    'missing token' => [null],
    'token of another size' => ['116354d576564cedd30c850f6a2f2dee'],
]);

it('does not route a path without dimensions to the thumbnail controller', function () {
    // Laravel may answer itself on /storage (its served local disk), so only the matched route is the package contract
    $route = rescue(
        fn () => Route::getRoutes()->match(Request::create('/storage/web_attachments/page/photo.jpg')),
        report: false,
    );

    expect($route?->getActionName())->not->toBe(JsonImageController::class . '@handle');
});

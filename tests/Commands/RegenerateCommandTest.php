<?php

declare(strict_types=1);

use GalleryJsonMedia\Tests\Fixtures\Models\Page;
use Illuminate\Support\Facades\Storage;

it('renders again the existing thumbnails from their image', function () {
    Storage::fake('public');
    storedImage('web_attachments/page/photo.jpg', 800, 600);
    Storage::disk('public')->put('web_attachments/page/photo-200x150.jpg', 'old thumbnail');
    Storage::disk('public')->put('web_attachments/page/photo-200x150.jpg.webp', 'old converted thumbnail');

    $this->artisan('gallery-json-media:regenerate')->assertSuccessful();

    $thumbnail = getimagesize(Storage::disk('public')->path('web_attachments/page/photo-200x150.jpg'));
    $converted = getimagesize(Storage::disk('public')->path('web_attachments/page/photo-200x150.jpg.webp'));
    expect([$thumbnail[0], $thumbnail[1], $thumbnail['mime']])->toBe([200, 150, 'image/jpeg'])
        ->and([$converted[0], $converted[1], $converted['mime']])->toBe([200, 150, 'image/webp']);
});

it('renders again the thumbnails of the given disks and keeps their visibility', function () {
    $disk = fakeRemoteDisk();
    storedImage('web_attachments/page/photo.jpg', 800, 600, disk: 's3', visibility: 'private');
    $disk->put('web_attachments/page/photo-200x150.jpg', 'old thumbnail', 'private');

    $this->artisan('gallery-json-media:regenerate', ['--disk' => ['s3']])->assertSuccessful();

    expect(array_slice(getimagesizefromstring($disk->get('web_attachments/page/photo-200x150.jpg')), 0, 2))->toBe([200, 150])
        ->and($disk->getVisibility('web_attachments/page/photo-200x150.jpg'))->toBe('private');
});

it('skips the thumbnails whose image is missing', function () {
    Storage::fake('public');
    Storage::disk('public')->put('web_attachments/page/gone-200x150.jpg', 'orphan thumbnail');

    $this->artisan('gallery-json-media:regenerate')->assertSuccessful();

    expect(Storage::disk('public')->get('web_attachments/page/gone-200x150.jpg'))->toBe('orphan thumbnail');
});

it('fails and keeps the thumbnails that cannot be rendered', function () {
    Storage::fake('public');
    Storage::disk('public')->put('web_attachments/page/photo.jpg', 'not an image');
    Storage::disk('public')->put('web_attachments/page/photo-200x150.jpg', 'old thumbnail');

    $this->artisan('gallery-json-media:regenerate')
        ->expectsOutputToContain('web_attachments/page/photo-200x150.jpg')
        ->assertFailed();

    expect(Storage::disk('public')->get('web_attachments/page/photo-200x150.jpg'))->toBe('old thumbnail');
});

it('does not overwrite a used file whose name looks like a thumbnail', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.maintenance.models', [Page::class => ['images']]);
    storedImage('web_attachments/page/banner.jpg', 800, 600);
    Page::create(['images' => [storedImage('web_attachments/page/banner-1920x1080.jpg', 640, 480)]]);

    $this->artisan('gallery-json-media:regenerate')->assertSuccessful();

    expect(array_slice(getimagesize(Storage::disk('public')->path('web_attachments/page/banner-1920x1080.jpg')), 0, 2))
        ->toBe([640, 480]);
});

it('never renders a file outside the media directory', function () {
    Storage::fake('public');
    storedImage('avatars/user.jpg', 800, 600);
    Storage::disk('public')->put('avatars/user-200x150.jpg', 'not a thumbnail of the package');

    $this->artisan('gallery-json-media:regenerate')->assertSuccessful();

    expect(Storage::disk('public')->get('avatars/user-200x150.jpg'))->toBe('not a thumbnail of the package');
});

it('records the size of the images saved without it, without touching the records', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.maintenance.models', [Page::class => ['images', 'documents']]);
    $sized = [...storedImage('web_attachments/page/sized.jpg', 320, 240), 'width' => 32, 'height' => 24];
    $logo = ['file' => 'web_attachments/page/logo.svg', 'disk' => 'public', 'mime_type' => 'image/svg+xml'];
    $document = storedDocument('web_attachments/page/brochure.pdf');
    $this->travelTo('2026-01-01 00:00:00');
    $page = Page::create([
        'images' => [storedImage('web_attachments/page/photo.jpg', 800, 600), $sized, $logo],
        'documents' => [$document],
    ]);
    $this->travelTo('2026-02-01 00:00:00');

    $this->artisan('gallery-json-media:regenerate')
        ->expectsOutputToContain('1 image completed with their size')
        ->assertSuccessful();

    $page->refresh();
    expect($page->images[0])->toMatchArray(['file' => 'web_attachments/page/photo.jpg', 'width' => 800, 'height' => 600])
        ->and($page->images[1])->toBe($sized)
        ->and($page->images[2])->toBe($logo)
        ->and($page->documents)->toBe([$document])
        ->and($page->updated_at->toDateString())->toBe('2026-01-01');
});

it('records the size of the images of the given disks only', function () {
    $disk = fakeRemoteDisk();
    Storage::fake('public');
    config()->set('gallery-json-media.maintenance.models', [Page::class => ['images']]);
    $page = Page::create(['images' => [
        storedImage('web_attachments/page/remote.jpg', 800, 600, disk: 's3'),
        storedImage('web_attachments/page/local.jpg', 640, 480),
    ]]);

    $this->artisan('gallery-json-media:regenerate', ['--disk' => ['s3']])->assertSuccessful();

    expect($page->refresh()->images[0])->toMatchArray(['width' => 800, 'height' => 600])
        ->and($page->images[1])->not->toHaveKeys(['width', 'height']);
});

it('fails and keeps without size the images that cannot be read', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.maintenance.models', [Page::class => ['images']]);
    Storage::disk('public')->put('web_attachments/page/broken.jpg', 'not an image');
    $missing = storedImage('web_attachments/page/missing.jpg');
    Storage::disk('public')->delete('web_attachments/page/missing.jpg');
    $page = Page::create(['images' => [
        ['file' => 'web_attachments/page/broken.jpg', 'disk' => 'public', 'mime_type' => 'image/jpeg'],
        $missing,
    ]]);

    $this->artisan('gallery-json-media:regenerate')
        ->expectsOutputToContain('web_attachments/page/broken.jpg')
        ->expectsOutputToContain('web_attachments/page/missing.jpg')
        ->assertFailed();

    expect($page->refresh()->images[0])->not->toHaveKeys(['width', 'height'])
        ->and($page->images[1])->toBe($missing);
});

it('only records the size of the images with --dimensions-only', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.maintenance.models', [Page::class => ['images']]);
    $page = Page::create(['images' => [storedImage('web_attachments/page/photo.jpg', 800, 600)]]);
    Storage::disk('public')->put('web_attachments/page/photo-200x150.jpg', 'old thumbnail');

    $this->artisan('gallery-json-media:regenerate', ['--dimensions-only' => true])->assertSuccessful();

    expect($page->refresh()->images[0])->toMatchArray(['width' => 800, 'height' => 600])
        ->and(Storage::disk('public')->get('web_attachments/page/photo-200x150.jpg'))->toBe('old thumbnail');
});

it('only renders the thumbnails again with --thumbnails-only', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.maintenance.models', [Page::class => ['images']]);
    $page = Page::create(['images' => [storedImage('web_attachments/page/photo.jpg', 800, 600)]]);
    Storage::disk('public')->put('web_attachments/page/photo-200x150.jpg', 'old thumbnail');

    $this->artisan('gallery-json-media:regenerate', ['--thumbnails-only' => true])->assertSuccessful();

    expect($page->refresh()->images[0])->not->toHaveKeys(['width', 'height'])
        ->and(array_slice(getimagesize(Storage::disk('public')->path('web_attachments/page/photo-200x150.jpg')), 0, 2))->toBe([200, 150]);
});

it('refuses --thumbnails-only with --dimensions-only', function () {
    Storage::fake('public');
    Storage::disk('public')->put('web_attachments/page/photo-200x150.jpg', 'old thumbnail');
    storedImage('web_attachments/page/photo.jpg', 800, 600);

    $this->artisan('gallery-json-media:regenerate', ['--thumbnails-only' => true, '--dimensions-only' => true])
        ->expectsOutputToContain('exclude each other')
        ->assertExitCode(2);

    expect(Storage::disk('public')->get('web_attachments/page/photo-200x150.jpg'))->toBe('old thumbnail');
});

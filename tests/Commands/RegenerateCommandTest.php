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

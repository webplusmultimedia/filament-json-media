<?php

declare(strict_types=1);

use GalleryJsonMedia\Tests\Fixtures\Models\Page;
use GalleryJsonMedia\Tests\Fixtures\Models\SoftDeletablePage;
use Illuminate\Support\Facades\Storage;

it('deletes the thumbnails whose image is missing and keeps the other files when no model is declared', function () {
    Storage::fake('public');
    storedImage('web_attachments/page/photo.jpg');
    Storage::disk('public')->put('web_attachments/page/photo-200x150.jpg', 'thumbnail');
    Storage::disk('public')->put('web_attachments/page/gone-200x150.jpg', 'orphan thumbnail');
    Storage::disk('public')->put('web_attachments/page/gone-200x150.jpg.webp', 'orphan converted thumbnail');
    storedDocument('web_attachments/page/brochure.pdf');

    $this->artisan('gallery-json-media:clean', ['--force' => true])->assertSuccessful();

    Storage::disk('public')->assertMissing(['web_attachments/page/gone-200x150.jpg', 'web_attachments/page/gone-200x150.jpg.webp']);
    Storage::disk('public')->assertExists([
        'web_attachments/page/photo.jpg',
        'web_attachments/page/photo-200x150.jpg',
        'web_attachments/page/brochure.pdf',
    ]);
});

it('deletes the files that no declared record uses, with their thumbnails', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.maintenance.models', [Page::class => ['images', 'documents']]);
    Page::create([
        'images' => [storedImage('web_attachments/page/photo.jpg')],
        'documents' => [storedDocument('web_attachments/page/brochure.pdf')],
    ]);
    Storage::disk('public')->put('web_attachments/page/photo-200x150.jpg.webp', 'thumbnail');
    storedImage('web_attachments/page/orphan.jpg');
    Storage::disk('public')->put('web_attachments/page/orphan-200x150.jpg', 'thumbnail of an orphan');
    storedDocument('web_attachments/page/old.pdf');

    $this->artisan('gallery-json-media:clean', ['--force' => true])->assertSuccessful();

    Storage::disk('public')->assertMissing([
        'web_attachments/page/orphan.jpg',
        'web_attachments/page/orphan-200x150.jpg',
        'web_attachments/page/old.pdf',
    ]);
    Storage::disk('public')->assertExists([
        'web_attachments/page/photo.jpg',
        'web_attachments/page/photo-200x150.jpg.webp',
        'web_attachments/page/brochure.pdf',
    ]);
});

it('keeps a used file whose name looks like a thumbnail', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.maintenance.models', [Page::class => ['images']]);
    Page::create(['images' => [storedImage('web_attachments/page/banner-1920x1080.jpg')]]);

    $this->artisan('gallery-json-media:clean', ['--force' => true])->assertSuccessful();

    Storage::disk('public')->assertExists('web_attachments/page/banner-1920x1080.jpg');
});

it('cleans the given disks and keeps the files their entries use', function () {
    $disk = fakeRemoteDisk();
    Storage::fake('public');
    config()->set('gallery-json-media.maintenance.models', [Page::class => ['images']]);
    Page::create(['images' => [storedImage('web_attachments/page/photo.jpg', disk: 's3')]]);
    storedImage('web_attachments/page/orphan.jpg', disk: 's3');
    storedImage('web_attachments/page/not-cleaned.jpg');

    $this->artisan('gallery-json-media:clean', ['--disk' => ['s3'], '--force' => true])->assertSuccessful();

    $disk->assertMissing('web_attachments/page/orphan.jpg');
    $disk->assertExists('web_attachments/page/photo.jpg');
    Storage::disk('public')->assertExists('web_attachments/page/not-cleaned.jpg');
});

it('keeps on every disk the files of the entries saved without disk', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.maintenance.models', [Page::class => ['images']]);
    $entry = storedImage('web_attachments/page/photo.jpg');
    unset($entry['disk']);
    Page::create(['images' => [$entry]]);

    $this->artisan('gallery-json-media:clean', ['--force' => true])->assertSuccessful();

    Storage::disk('public')->assertExists('web_attachments/page/photo.jpg');
});

it('never deletes a file outside the media directory', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.maintenance.models', [Page::class => ['images']]);
    storedImage('avatars/user.jpg');
    Storage::disk('public')->put('avatars/gone-200x150.jpg', 'thumbnail look-alike');

    $this->artisan('gallery-json-media:clean', ['--force' => true])->assertSuccessful();

    Storage::disk('public')->assertExists(['avatars/user.jpg', 'avatars/gone-200x150.jpg']);
});

it('lists the files to delete without deleting them on a dry run', function () {
    Storage::fake('public');
    Storage::disk('public')->put('web_attachments/page/gone-200x150.jpg', 'orphan thumbnail');

    $this->artisan('gallery-json-media:clean', ['--dry-run' => true])
        ->expectsOutputToContain('web_attachments/page/gone-200x150.jpg')
        ->assertSuccessful();

    Storage::disk('public')->assertExists('web_attachments/page/gone-200x150.jpg');
});

it('deletes the files only once confirmed', function (string $answer, bool $deleted) {
    Storage::fake('public');
    Storage::disk('public')->put('web_attachments/page/gone-200x150.jpg', 'orphan thumbnail');

    $this->artisan('gallery-json-media:clean')
        ->expectsConfirmation('Delete 1 file?', $answer)
        ->assertSuccessful();

    expect(Storage::disk('public')->exists('web_attachments/page/gone-200x150.jpg'))->toBe(! $deleted);
})->with([
    'confirmed' => ['yes', true],
    'refused' => ['no', false],
]);

it('says when there is nothing to delete', function () {
    Storage::fake('public');
    storedImage('web_attachments/page/photo.jpg');

    $this->artisan('gallery-json-media:clean')
        ->expectsOutputToContain('Nothing to delete')
        ->assertSuccessful();
});

it('keeps the files of the soft deleted records', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.maintenance.models', [SoftDeletablePage::class => ['images']]);
    SoftDeletablePage::create(['images' => [storedImage('web_attachments/page/photo.jpg')]])->delete();

    $this->artisan('gallery-json-media:clean', ['--force' => true])->assertSuccessful();

    Storage::disk('public')->assertExists('web_attachments/page/photo.jpg');
});

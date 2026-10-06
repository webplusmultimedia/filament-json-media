<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\Document;
use GalleryJsonMedia\JsonMedia\Media;
use GalleryJsonMedia\Tests\Fixtures\Models\JsonMediaCastPage;
use GalleryJsonMedia\Tests\Fixtures\Models\Page;
use GalleryJsonMedia\Tests\Fixtures\Models\SoftDeletablePage;
use Illuminate\Support\Facades\Storage;

it('returns only images as medias and only other files as documents', function () {
    Storage::fake('public');
    $page = new Page([
        'images' => [
            storedImage('web_attachments/page/photo.jpg', alt: 'Photo'),
            storedDocument('web_attachments/page/brochure.pdf', alt: 'Brochure'),
        ],
    ]);

    $medias = $page->getMedias('images');
    $documents = $page->getDocuments('images');

    expect($medias)->toHaveCount(1)
        ->and($medias[0])->toBeInstanceOf(Media::class)
        ->and($medias[0]->getCustomProperty('alt'))->toBe('Photo');
    expect($documents)->toHaveCount(1)
        ->and($documents[0])->toBeInstanceOf(Document::class)
        ->and($documents[0]->getCustomProperty('alt'))->toBe('Brochure');
});

it('treats an entry without mime type as an image', function () {
    Storage::fake('public');
    $entry = storedImage('web_attachments/page/photo.jpg');
    unset($entry['mime_type']);
    $page = new Page(['images' => [$entry]]);

    expect($page->getMedias('images'))->toHaveCount(1)
        ->and($page->getDocuments('images'))->toBeEmpty();
});

it('returns no media and no document when the field is empty', function () {
    $page = new Page(['images' => null]);

    expect($page->getMedias('images'))->toBe([])
        ->and($page->getDocuments('images'))->toBe([])
        ->and($page->getFirstMedia('images'))->toBeNull()
        ->and($page->getFirstMediaUrl('images'))->toBeNull()
        ->and($page->getFirstMediaCropUrl('images', 200, 150))->toBeNull()
        ->and($page->hasDocuments('images'))->toBeFalse()
        ->and($page->mediasCount('images'))->toBe(0)
        ->and($page->documentsCount('images'))->toBe(0);
});

it('returns the first image url and the other images apart', function () {
    Storage::fake('public');
    $page = new Page([
        'images' => [
            storedImage('web_attachments/page/first.jpg', alt: 'First'),
            storedImage('web_attachments/page/second.jpg', alt: 'Second'),
            storedImage('web_attachments/page/third.jpg', alt: 'Third'),
        ],
    ]);

    $others = $page->getMediasWithoutFirst('images');

    expect($page->getFirstMediaUrl('images'))->toBe('/storage/web_attachments/page/first.jpg');
    expect($others)->toHaveCount(2)
        ->and($others[0]->getCustomProperty('alt'))->toBe('Second')
        ->and($others[1]->getCustomProperty('alt'))->toBe('Third');
});

it('counts images and documents separately', function () {
    Storage::fake('public');
    $page = new Page([
        'documents' => [
            storedImage('web_attachments/page/photo.jpg'),
            storedDocument('web_attachments/page/first.pdf'),
            storedDocument('web_attachments/page/second.pdf'),
        ],
    ]);

    expect($page->mediasCount('documents'))->toBe(1)
        ->and($page->documentsCount('documents'))->toBe(2)
        ->and($page->hasDocuments('documents'))->toBeTrue();
});

it('deletes the files and the thumbnails of the declared fields when the model is deleted', function () {
    Storage::fake('public');
    $page = Page::create([
        'images' => [storedImage('web_attachments/page/photo.jpg')],
        'documents' => [storedDocument('web_attachments/page/brochure.pdf')],
    ]);
    Storage::disk('public')->put('web_attachments/page/photo-200x150.jpg', 'thumbnail');

    $page->delete();

    Storage::disk('public')->assertMissing([
        'web_attachments/page/photo.jpg',
        'web_attachments/page/photo-200x150.jpg',
        'web_attachments/page/brochure.pdf',
    ]);
});

it('keeps the files when a soft deletable model is soft deleted', function () {
    Storage::fake('public');
    $page = SoftDeletablePage::create([
        'images' => [storedImage('web_attachments/page/photo.jpg')],
        'documents' => [storedDocument('web_attachments/page/brochure.pdf')],
    ]);

    $page->delete();

    $this->assertSoftDeleted($page);
    Storage::disk('public')->assertExists([
        'web_attachments/page/photo.jpg',
        'web_attachments/page/brochure.pdf',
    ]);
});

it('deletes the files when a soft deletable model is force deleted', function () {
    Storage::fake('public');
    $page = SoftDeletablePage::create([
        'images' => [storedImage('web_attachments/page/photo.jpg')],
        'documents' => [storedDocument('web_attachments/page/brochure.pdf')],
    ]);

    $page->forceDelete();

    $this->assertModelMissing($page);
    Storage::disk('public')->assertMissing([
        'web_attachments/page/photo.jpg',
        'web_attachments/page/brochure.pdf',
    ]);
});

it('deletes the files and the thumbnails stored on a remote disk when the model is deleted', function () {
    $disk = fakeRemoteDisk();
    $page = Page::create([
        'images' => [storedImage('web_attachments/page/photo.jpg', disk: 's3', visibility: 'private')],
        'documents' => [storedDocument('web_attachments/page/brochure.pdf', disk: 's3', visibility: 'private')],
    ]);
    $disk->put('web_attachments/page/photo-200x150.jpg', 'thumbnail');

    $page->delete();

    $disk->assertMissing([
        'web_attachments/page/photo.jpg',
        'web_attachments/page/photo-200x150.jpg',
        'web_attachments/page/brochure.pdf',
    ]);
});

it('returns the medias and the documents of a field cast to json medias', function () {
    Storage::fake('public');
    $page = JsonMediaCastPage::create([
        'images' => [
            storedImage('web_attachments/page/photo.jpg', alt: 'Photo'),
            storedDocument('web_attachments/page/brochure.pdf', alt: 'Brochure'),
        ],
    ]);

    $medias = $page->refresh()->getMedias('images');
    $documents = $page->getDocuments('images');

    expect($medias)->toHaveCount(1)
        ->and($medias[0]->getCustomProperty('alt'))->toBe('Photo')
        ->and($documents)->toHaveCount(1)
        ->and($documents[0]->getCustomProperty('alt'))->toBe('Brochure')
        ->and($page->getFirstMediaUrl('images'))->toBe('/storage/web_attachments/page/photo.jpg');
});

it('deletes the files of a field cast to json medias when the model is deleted', function () {
    Storage::fake('public');
    $page = JsonMediaCastPage::create([
        'images' => [storedImage('web_attachments/page/photo.jpg')],
        'documents' => [storedDocument('web_attachments/page/brochure.pdf')],
    ]);

    $page->refresh()->delete();

    Storage::disk('public')->assertMissing(['web_attachments/page/photo.jpg', 'web_attachments/page/brochure.pdf']);
});

it('deletes the converted thumbnails of the declared fields when the model is deleted', function (string $disk) {
    $storage = $disk === 's3' ? fakeRemoteDisk() : Storage::fake($disk);
    $page = Page::create(['images' => [storedImage('web_attachments/page/photo.jpg', disk: $disk)]]);
    $storage->put('web_attachments/page/photo-200x150.jpg.webp', 'converted thumbnail');

    $page->delete();

    $storage->assertMissing(['web_attachments/page/photo.jpg', 'web_attachments/page/photo-200x150.jpg.webp']);
})->with(['local disk' => 'public', 'remote disk' => 's3']);

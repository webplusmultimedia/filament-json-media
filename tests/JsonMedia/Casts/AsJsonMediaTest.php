<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\Document;
use GalleryJsonMedia\JsonMedia\Media;
use GalleryJsonMedia\Tests\Fixtures\Models\JsonMediaCastPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

it('reads the images as medias and the other files as documents', function () {
    Storage::fake('public');
    $page = JsonMediaCastPage::create([
        'images' => [
            storedImage('web_attachments/page/photo.jpg', alt: 'Photo'),
            storedDocument('web_attachments/page/brochure.pdf', alt: 'Brochure'),
        ],
    ]);

    $images = $page->refresh()->images;

    expect($images)->toBeInstanceOf(Collection::class)->toHaveCount(2)
        ->and($images[0])->toBeInstanceOf(Media::class)
        ->and($images[0]->getCustomProperty('alt'))->toBe('Photo')
        ->and($images[1])->toBeInstanceOf(Document::class)
        ->and($images[1]->getCustomProperty('alt'))->toBe('Brochure');
});

it('reads an empty field as an empty collection', function () {
    $page = JsonMediaCastPage::create();

    expect($page->refresh()->images)->toBeInstanceOf(Collection::class)->toBeEmpty();
});

it('stores medias and documents as their json entries', function () {
    Storage::fake('public');
    $image = storedImage('web_attachments/page/photo.jpg');
    $document = storedDocument('web_attachments/page/brochure.pdf');
    $page = JsonMediaCastPage::create();

    $page->update(['images' => [Media::make($image), Document::make($document)]]);

    expect(json_decode($page->refresh()->getRawOriginal('images'), true))->toBe([$image, $document]);
});

it('serializes the field as its json entries', function () {
    Storage::fake('public');
    $image = storedImage('web_attachments/page/photo.jpg');
    $page = JsonMediaCastPage::create(['images' => [$image]]);

    expect($page->refresh()->toArray()['images'])->toBe([$image]);
});

it('does not change a field that is only read', function () {
    $page = JsonMediaCastPage::create();

    $page->images;

    expect($page->isDirty('images'))->toBeFalse();
});

<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\Document;
use Illuminate\Support\Facades\Storage;

it('returns the url of the stored file', function () {
    Storage::fake('public');
    $document = Document::make(storedDocument('web_attachments/page/brochure.pdf'));

    expect($document->getUrl())->toBe('/storage/web_attachments/page/brochure.pdf')
        ->and((string) $document)->toBe('/storage/web_attachments/page/brochure.pdf');
});

it('returns no url when the file is missing from the disk', function () {
    Storage::fake('public');
    $entry = storedDocument('web_attachments/page/brochure.pdf');
    Storage::disk('public')->delete('web_attachments/page/brochure.pdf');

    $document = Document::make($entry);

    expect($document->getUrl())->toBeNull()
        ->and((string) $document)->toBe('');
});

it('returns a custom property and null for an unknown one', function () {
    $document = Document::make(['customProperties' => ['alt' => 'Brochure']]);

    expect($document->getCustomProperty('alt'))->toBe('Brochure')
        ->and($document->getCustomProperty('unknown'))->toBeNull();
});

it('deletes the file from its disk', function () {
    Storage::fake('public');
    $document = Document::make(storedDocument('web_attachments/page/brochure.pdf'));

    $document->delete();

    Storage::disk('public')->assertMissing('web_attachments/page/brochure.pdf');
});

it('returns a temporary url for a private document', function () {
    $this->travelTo('2026-01-01 00:00:00');
    fakeRemoteDisk();
    $document = Document::make(storedDocument('page/brochure.pdf', disk: 's3', visibility: 'private'));

    expect($document->getUrl())->toBe('https://bucket.test/page/brochure.pdf?expires=1767225900');
});

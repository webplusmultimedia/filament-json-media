<?php

declare(strict_types=1);

use GalleryJsonMedia\Tables\Columns\JsonMediaColumn;
use GalleryJsonMedia\Tests\Fixtures\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

it('renders the first image as a thumbnail', function () {
    Storage::fake('public');
    $page = new Page([
        'images' => [
            storedImage('web_attachments/page/first.jpg', alt: 'First'),
            storedImage('web_attachments/page/second.jpg', alt: 'Second'),
        ],
    ]);

    $html = JsonMediaColumn::make('images')->record($page)->toEmbeddedHtml();

    expect($html)
        ->toContain('src="/storage/web_attachments/page/first-40x40.jpg?disk=public&amp;signature=')
        ->toContain('alt="First"')
        ->not->toContain('Second');
});

it('renders the avatars up to the maximum and counts the others', function () {
    Storage::fake('public');
    $page = new Page([
        'images' => [
            storedImage('web_attachments/page/first.jpg'),
            storedImage('web_attachments/page/second.jpg'),
            storedImage('web_attachments/page/third.jpg'),
        ],
    ]);

    $html = JsonMediaColumn::make('images')->avatars()->maxAvatar(2)->record($page)->toEmbeddedHtml();

    expect(substr_count($html, '<img'))->toBe(2);
    expect($html)
        ->toContain('first-40x40.jpg')
        ->toContain('second-40x40.jpg')
        ->not->toContain('third-40x40.jpg')
        ->toContain('+1');
});

it('escapes the alt text of the avatars', function () {
    Storage::fake('public');
    $page = new Page(['images' => [storedImage('web_attachments/page/photo.jpg', alt: '"><script>alert(1)</script>')]]);

    $html = JsonMediaColumn::make('images')->avatars()->record($page)->toEmbeddedHtml();

    expect($html)
        ->toContain('&lt;script&gt;')
        ->not->toContain('<script>alert(1)</script>');
});

it('renders an error when the model does not implement HasMedia', function () {
    $record = new class extends Model {};

    $html = JsonMediaColumn::make('images')->record($record)->toEmbeddedHtml();

    expect($html)->toContain('HasMedia interface not implemented on the model');
});

<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use GalleryJsonMedia\Tests\Fixtures\Models\Page;
use GalleryJsonMedia\Tests\Fixtures\PageForm;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function fileKeyOf(Testable $component, string $file): string
{
    return collect($component->get('data.images'))
        ->search(fn (array $entry): bool => $entry['file'] === $file);
}

it('stores an uploaded image in the gallery directory with its metadata', function () {
    Storage::fake('public');
    $page = Page::create();

    Livewire::test(PageForm::class, ['record' => $page])
        ->set('data.images.new-photo', UploadedFile::fake()->image('mon-super_chat.jpg', 640, 480))
        ->call('save')
        ->assertHasNoFormErrors();

    $images = array_values($page->refresh()->images);
    expect($images)->toHaveCount(1)
        ->and($images[0])->toMatchArray([
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'customProperties' => ['alt' => 'Mon super chat', 'title' => null],
        ])
        ->and($images[0])->not->toHaveKey('deleted')
        ->and($images[0]['file'])->toStartWith('web_attachments/page/');
    Storage::disk('public')->assertExists($images[0]['file']);
    expect($images[0]['size'])->toBe(Storage::disk('public')->size($images[0]['file']));
});

it('deletes a removed image and its thumbnails when the form is saved', function () {
    Storage::fake('public');
    $page = Page::create([
        'images' => [
            storedImage('web_attachments/page/photo.jpg'),
            storedImage('web_attachments/page/other.jpg'),
        ],
    ]);
    Storage::disk('public')->put('web_attachments/page/photo-300x230.jpg', 'thumbnail');
    $component = Livewire::test(PageForm::class, ['record' => $page]);

    $component
        ->call('callSchemaComponentMethod', 'form.images', 'deleteUploadedFile', ['fileKey' => fileKeyOf($component, 'web_attachments/page/photo.jpg')])
        ->call('save');

    expect(array_column(array_values($page->refresh()->images), 'file'))->toBe(['web_attachments/page/other.jpg']);
    Storage::disk('public')->assertMissing(['web_attachments/page/photo.jpg', 'web_attachments/page/photo-300x230.jpg']);
    Storage::disk('public')->assertExists('web_attachments/page/other.jpg');
});

it('keeps a removed image on the disk until the form is saved', function () {
    Storage::fake('public');
    $page = Page::create(['images' => [storedImage('web_attachments/page/photo.jpg')]]);
    $component = Livewire::test(PageForm::class, ['record' => $page]);

    $component->call('callSchemaComponentMethod', 'form.images', 'deleteUploadedFile', ['fileKey' => fileKeyOf($component, 'web_attachments/page/photo.jpg')]);

    Storage::disk('public')->assertExists('web_attachments/page/photo.jpg');
    expect(array_column($page->refresh()->images, 'file'))->toBe(['web_attachments/page/photo.jpg']);
});

it('rejects a file that is not an accepted image type', function () {
    Storage::fake('public');
    $page = Page::create();

    $component = Livewire::test(PageForm::class, ['record' => $page])
        ->set('data.images.new-file', UploadedFile::fake()->create('brochure.pdf', 10, 'application/pdf'))
        ->call('save');

    // The message holds a colon, which assertHasFormErrors() would read as a rule with parameters
    expect($component->errors()->get('data.images'))
        ->toBe(['The images field must be a file of type: image/jpeg, image/png, image/svg+xml, image/webp, image/avif.']);
    expect($page->refresh()->images)->toBeNull();
    Storage::disk('public')->assertDirectoryEmpty('web_attachments');
});

it('rejects a file larger than the maximum size', function () {
    Storage::fake('public');
    $page = Page::create();

    Livewire::test(PageForm::class, ['record' => $page])
        ->set('data.images.new-photo', UploadedFile::fake()->image('photo.jpg')->size(2048))
        ->call('save')
        ->assertHasFormErrors(['images' => 'The images field must not be greater than 1024 kilobytes.']);

    expect($page->refresh()->images)->toBeNull();
    Storage::disk('public')->assertDirectoryEmpty('web_attachments');
});

it('rejects more files than the maximum allowed', function () {
    Storage::fake('public');
    $page = Page::create();

    Livewire::test(PageForm::class, ['record' => $page])
        ->set('data.images.first', UploadedFile::fake()->image('first.jpg'))
        ->set('data.images.second', UploadedFile::fake()->image('second.jpg'))
        ->set('data.images.third', UploadedFile::fake()->image('third.jpg'))
        ->call('save')
        ->assertHasFormErrors(['images' => 'max']);

    expect($page->refresh()->images)->toBeNull();
});

it('lists the stored files with a thumbnail url for bitmaps and the original url for svg', function () {
    Storage::fake('public');
    Storage::disk('public')->put('web_attachments/page/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
    $page = Page::create([
        'images' => [
            storedImage('web_attachments/page/photo.jpg', alt: 'Photo'),
            ['file' => 'web_attachments/page/logo.svg', 'disk' => 'public', 'mime_type' => 'image/svg+xml', 'size' => 42, 'customProperties' => ['alt' => 'Logo']],
        ],
    ]);
    $component = Livewire::test(PageForm::class, ['record' => $page]);

    $files = $component->instance()->getSchemaComponent('form.images')->getUploadedFiles();

    expect(array_values($files))->toBe([
        [
            'name' => 'web_attachments/page/photo.jpg',
            'size' => $page->images[0]['size'],
            'alt' => 'Photo',
            'mime_type' => 'image/jpeg',
            'url' => '/storage/web_attachments/page/photo-300x230.jpg?_token=116354d576564cedd30c850f6a2f2dee',
        ],
        [
            'name' => 'web_attachments/page/logo.svg',
            'size' => 42,
            'alt' => 'Logo',
            'mime_type' => 'image/svg+xml',
            'url' => '/storage/web_attachments/page/logo.svg',
        ],
    ]);
});

it('does not list removed files nor files missing from the disk', function () {
    Storage::fake('public');
    $page = Page::create([
        'images' => [
            storedImage('web_attachments/page/photo.jpg'),
            storedImage('web_attachments/page/removed.jpg'),
            storedImage('web_attachments/page/missing.jpg'),
        ],
    ]);
    Storage::disk('public')->delete('web_attachments/page/missing.jpg');
    $component = Livewire::test(PageForm::class, ['record' => $page]);
    $component->call('callSchemaComponentMethod', 'form.images', 'deleteUploadedFile', ['fileKey' => fileKeyOf($component, 'web_attachments/page/removed.jpg')]);

    $files = $component->instance()->getSchemaComponent('form.images')->getUploadedFiles();

    expect(array_column($files, 'name'))->toBe(['web_attachments/page/photo.jpg']);
});

it('saves the custom properties edited for a file', function () {
    Storage::fake('public');
    $page = Page::create(['images' => [storedImage('web_attachments/page/photo.jpg', alt: 'Old alt')]]);
    $component = Livewire::test(PageForm::class, ['record' => $page]);
    $action = TestAction::make('edit-custom-properties')
        ->schemaComponent('images', schema: 'form')
        ->arguments(['key' => fileKeyOf($component, 'web_attachments/page/photo.jpg')]);

    $component
        ->callAction($action, data: ['alt' => 'New alt'])
        ->assertHasNoFormErrors()
        ->call('save');

    expect($page->refresh()->images[array_key_first($page->images)]['customProperties'])->toBe(['alt' => 'New alt']);
});

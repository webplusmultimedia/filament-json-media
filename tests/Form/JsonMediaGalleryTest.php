<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use GalleryJsonMedia\Tests\Fixtures\Models\CollectionCastPage;
use GalleryJsonMedia\Tests\Fixtures\Models\JsonMediaCastPage;
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
            'visibility' => 'public',
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

    [$photo, $logo] = array_values($component->instance()->getSchemaComponent('form.images')->getUploadedFiles());

    expect($photo)->toMatchArray([
        'name' => 'web_attachments/page/photo.jpg',
        'size' => $page->images[0]['size'],
        'alt' => 'Photo',
        'mime_type' => 'image/jpeg',
    ]);
    expect($photo['url'])->toStartWith('/storage/web_attachments/page/photo-300x230.jpg?disk=public&signature=');
    expect($logo)->toBe([
        'name' => 'web_attachments/page/logo.svg',
        'size' => 42,
        'alt' => 'Logo',
        'mime_type' => 'image/svg+xml',
        'url' => '/storage/web_attachments/page/logo.svg',
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

it('rejects a removal request for a file that does not belong to the record and keeps that file', function () {
    Storage::fake('public');
    Storage::disk('public')->put('private/other-user/secret.pdf', 'secret');
    $page = Page::create(['images' => [storedImage('web_attachments/page/photo.jpg')]]);

    $component = Livewire::test(PageForm::class, ['record' => $page])
        ->set('data.images.injected', [
            'file' => 'private/other-user/secret.pdf',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 6,
            'deleted' => true,
            'customProperties' => ['alt' => 'Injected'],
        ])
        ->call('save');

    expect($component->errors()->get('data.images'))
        ->toBe(['The images field contains a file path that is not permitted.']);
    Storage::disk('public')->assertExists('private/other-user/secret.pdf');
    expect(array_column($page->refresh()->images, 'file'))->toBe(['web_attachments/page/photo.jpg']);
});

it('rejects a file of another record', function () {
    Storage::fake('public');
    $other = Page::create(['images' => [storedImage('web_attachments/page/other.jpg')]]);
    $page = Page::create(['images' => [storedImage('web_attachments/page/photo.jpg')]]);

    Livewire::test(PageForm::class, ['record' => $page])
        ->set('data.images.injected', $other->images[0])
        ->call('save')
        ->assertHasFormErrors(['images']);

    expect(array_column($page->refresh()->images, 'file'))->toBe(['web_attachments/page/photo.jpg']);
});

it('rejects any stored file when creating a record', function () {
    Storage::fake('public');
    $other = Page::create(['images' => [storedImage('web_attachments/page/other.jpg')]]);

    Livewire::test(PageForm::class)
        ->set('data.images.injected', $other->images[0])
        ->call('save')
        ->assertHasFormErrors(['images']);

    expect(Page::count())->toBe(1);
});

it('neither keeps nor deletes an unknown file when the uploaded files are saved without validation', function () {
    Storage::fake('public');
    Storage::disk('public')->put('private/other-user/secret.pdf', 'secret');
    $page = Page::create(['images' => [storedImage('web_attachments/page/photo.jpg')]]);
    $component = Livewire::test(PageForm::class, ['record' => $page])
        ->set('data.images.injected', [
            'file' => 'private/other-user/secret.pdf',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 6,
            'deleted' => true,
            'customProperties' => ['alt' => 'Injected'],
        ]);
    $field = $component->instance()->getSchemaComponent('form.images');

    $field->saveUploadedFiles();

    Storage::disk('public')->assertExists('private/other-user/secret.pdf');
    expect(array_column($field->getRawState(), 'file'))->toBe(['web_attachments/page/photo.jpg']);
});

it('keeps the stored metadata of a file and only saves its edited custom properties', function () {
    Storage::fake('public');
    $photo = storedImage('web_attachments/page/photo.jpg', alt: 'Old alt');
    $page = Page::create(['images' => [$photo]]);
    $component = Livewire::test(PageForm::class, ['record' => $page]);
    $key = fileKeyOf($component, 'web_attachments/page/photo.jpg');

    $component
        ->set("data.images.{$key}.disk", 'local')
        ->set("data.images.{$key}.mime_type", 'image/svg+xml')
        ->set("data.images.{$key}.size", 1)
        ->set("data.images.{$key}.customProperties.alt", 'New alt')
        ->call('save')
        ->assertHasNoFormErrors();

    expect(array_values($page->refresh()->images))->toBe([
        [...$photo, 'customProperties' => ['alt' => 'New alt', 'title' => null]],
    ]);
});

it('accepts the stored files of a record whose field is cast to a collection', function () {
    Storage::fake('public');
    $page = CollectionCastPage::create(['images' => [storedImage('web_attachments/page/photo.jpg')]]);

    Livewire::test(PageForm::class, ['record' => $page])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->refresh()->images->pluck('file')->all())->toBe(['web_attachments/page/photo.jpg']);
});

it('keeps the stored files of a record whose field is cast to json medias and adds the uploads', function () {
    Storage::fake('public');
    $page = JsonMediaCastPage::create(['images' => [storedImage('web_attachments/page/photo.jpg')]]);

    Livewire::test(PageForm::class, ['record' => $page])
        ->set('data.images.new-photo', UploadedFile::fake()->image('chat.jpg'))
        ->call('save')
        ->assertHasNoFormErrors();

    $files = array_column($page->refresh()->images->toArray(), 'file');
    expect($files)->toHaveCount(2)
        ->and($files[0])->toBe('web_attachments/page/photo.jpg')
        ->and($files[1])->toStartWith('web_attachments/page/');
});

it('previews the stored files of a record whose field is cast to json medias', function () {
    Storage::fake('public');
    $page = JsonMediaCastPage::create(['images' => [storedImage('web_attachments/page/photo.jpg')]]);
    $component = Livewire::test(PageForm::class, ['record' => $page]);

    $files = $component->instance()->getSchemaComponent('form.images')->getUploadedFiles();

    expect(array_column($files, 'name'))->toBe(['web_attachments/page/photo.jpg']);
});

it('stores an upload on the disk of the field with its visibility', function () {
    $disk = fakeRemoteDisk();
    $page = Page::create();

    Livewire::test(PageForm::class, ['record' => $page, 'disk' => 's3', 'visibility' => 'private'])
        ->set('data.images.new-photo', UploadedFile::fake()->image('photo.jpg'))
        ->call('save')
        ->assertHasNoFormErrors();

    $image = array_values($page->refresh()->images)[0];
    expect($image)->toMatchArray(['disk' => 's3', 'visibility' => 'private']);
    $disk->assertExists($image['file']);
});

it('ignores a disk or a visibility changed by the client on a new upload', function () {
    fakeRemoteDisk();
    Storage::fake('public');
    $page = Page::create();

    Livewire::test(PageForm::class, ['record' => $page, 'disk' => 's3', 'visibility' => 'private'])
        ->set('data.images.new-photo', UploadedFile::fake()->image('photo.jpg'))
        ->set('data.images.new-photo.disk', 'public')
        ->set('data.images.new-photo.visibility', 'public')
        ->call('save')
        ->assertHasNoFormErrors();

    expect(array_values($page->refresh()->images)[0])->toMatchArray(['disk' => 's3', 'visibility' => 'private']);
});

it('deletes a removed remote image and its thumbnails when the form is saved', function () {
    $disk = fakeRemoteDisk();
    $page = Page::create(['images' => [storedImage('web_attachments/page/photo.jpg', disk: 's3', visibility: 'private')]]);
    $disk->put('web_attachments/page/photo-300x230.jpg', 'thumbnail');
    $component = Livewire::test(PageForm::class, ['record' => $page, 'disk' => 's3', 'visibility' => 'private']);

    $component
        ->call('callSchemaComponentMethod', 'form.images', 'deleteUploadedFile', ['fileKey' => fileKeyOf($component, 'web_attachments/page/photo.jpg')])
        ->call('save');

    expect($page->refresh()->images)->toBe([]);
    $disk->assertMissing(['web_attachments/page/photo.jpg', 'web_attachments/page/photo-300x230.jpg']);
});

it('previews remote images through the signed thumbnail route and documents through their temporary url', function () {
    $this->travelTo('2026-01-01 00:00:00');
    fakeRemoteDisk();
    $page = Page::create([
        'images' => [
            storedImage('web_attachments/page/photo.jpg', disk: 's3', visibility: 'private'),
            storedDocument('web_attachments/page/brochure.pdf', disk: 's3', visibility: 'private'),
        ],
    ]);
    $component = Livewire::test(PageForm::class, ['record' => $page, 'disk' => 's3', 'visibility' => 'private']);

    $urls = array_column($component->instance()->getSchemaComponent('form.images')->getUploadedFiles(), 'url');

    expect($urls[0])->toStartWith('http://localhost/gallery-json-media/thumbnails/s3/web_attachments/page/photo-300x230.jpg?')
        ->and($urls[1])->toBe('https://bucket.test/web_attachments/page/brochure.pdf?expires=1767225900');
});

it('does not preview a file that does not belong to the record', function () {
    Storage::fake('public');
    Storage::fake('local');
    storedImage('secret/scan.jpg', disk: 'local', visibility: 'private');
    storedDocument('secret/contract.pdf', disk: 'local', visibility: 'private');
    $other = Page::create(['images' => [storedImage('web_attachments/page/other.jpg')]]);
    $page = Page::create(['images' => [storedImage('web_attachments/page/photo.jpg')]]);
    $component = Livewire::test(PageForm::class, ['record' => $page])
        ->set('data.images.other-record', $other->images[0])
        ->set('data.images.injected-image', ['file' => 'secret/scan.jpg', 'disk' => 'local', 'visibility' => 'private', 'mime_type' => 'image/jpeg'])
        ->set('data.images.injected-document', ['file' => 'secret/contract.pdf', 'disk' => 'local', 'visibility' => 'private', 'mime_type' => 'application/pdf']);

    $files = $component->instance()->getSchemaComponent('form.images')->getUploadedFiles();

    expect(array_column($files, 'name'))->toBe(['web_attachments/page/photo.jpg']);
});

it('records the visibility of the package config when the field sets none', function () {
    fakeRemoteDisk();
    config()->set('gallery-json-media.disk', 's3');
    config()->set('gallery-json-media.visibility', 'public');
    $page = Page::create();

    Livewire::test(PageForm::class, ['record' => $page])
        ->set('data.images.new-photo', UploadedFile::fake()->image('photo.jpg'))
        ->call('save')
        ->assertHasNoFormErrors();

    expect(array_values($page->refresh()->images)[0])->toMatchArray(['disk' => 's3', 'visibility' => 'public']);
});

it('prefers the visibility of the field to the one of the package config', function () {
    Storage::fake('public');
    config()->set('gallery-json-media.visibility', 'public');
    $page = Page::create();

    Livewire::test(PageForm::class, ['record' => $page, 'visibility' => 'private'])
        ->set('data.images.new-photo', UploadedFile::fake()->image('photo.jpg'))
        ->call('save')
        ->assertHasNoFormErrors();

    expect(array_values($page->refresh()->images)[0]['visibility'])->toBe('private');
});

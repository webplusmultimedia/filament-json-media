# A Gallery media (Json) for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/webplusm/gallery-json-media.svg?style=flat-square)](https://packagist.org/packages/webplusm/gallery-json-media)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/webplusmultimedia/filament-json-media/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/webplusmultimedia/filament-json-media/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/webplusmultimedia/filament-json-media/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/webplusmultimedia/filament-json-media/actions/workflows/fix-php-code-styling.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/webplusm/gallery-json-media.svg?style=flat-square)](https://packagist.org/packages/webplusm/gallery-json-media)



This package add a json field media for images/documents to filament V3.x and fluents api for front-end in Laravel to display photos and url link for documents ...
[![json-media.webp](https://i.postimg.cc/8Cn6Zttf/json-media.webp)](https://postimg.cc/wtLMvcK9)
## Installation

You can install the package via composer:

```bash
composer require webplusm/gallery-json-media
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="gallery-json-media-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="gallery-json-media-views"
```

## Usage
### Prepare your model
```php
use GalleryJsonMedia\JsonMedia\Concerns\InteractWithMedia;
use GalleryJsonMedia\JsonMedia\Contracts\HasMedia;

class Page extends Model implements HasMedia
{
    use HasFactory;
    use InteractWithMedia;
    
    protected $casts =[
        'images' => 'array',
        'documents' => 'array',
    ];
    
    // for auto-delete media thumbnails
    protected function getFieldsToDeleteMedia(): array {
        return ['images','documents'];
    }
    ...
    
}
```

### In Filament Forms
```php
use GalleryJsonMedia\Form\JsonMediaGallery;
JsonMediaGallery::make('images')
    ->directory('page')
    ->reorderable()
    ->preserveFilenames()
    ->acceptedFileTypes()
    ->disk()
    ->visibility() // only public for now
    ->maxSize(4 * 1024)
    ->minSize()
    ->maxFiles()
    ->minFiles()
    ->image() // only images
    ->document() // only documents (eg: pdf, doc, xls,...)
    ->downloadable()
    ->deletable()
    ->withCustomProperties(
       customPropertiesSchema: [                                     
            ...
        ],
       editCustomPropertiesOnSlideOver: true,
       editCustomPropertiesTitle: "Edit customs properties")
```

### In Filament Tables
![table-filament-json-media.png](https://raw.githubusercontent.com/webplusmultimedia/filament-json-media/main/table-filament-json-media.png)
```php
use GalleryJsonMedia\Tables\Columns\JsonMediaColumn;
JsonMediaColumn::make('images')
    ->avatars(bool|Closure)
```
### In Filament Infolists
```php
use GalleryJsonMedia\Infolists\JsonMediaEntry;
JsonMediaEntry::make('images')
    ->avatars()
    ->thumbHeight(100)
    ->thumbWidth(100)
    ->visible(static fn(array|null $state)=> filled($state))
```

### In Blade Front-end
```html
/** for media */
 @foreach($page->getMedias('images') as $media)
        <div style="display: flex;gap: .5rem">
            {{ $media }}
        </div>
 @endforeach
 
 // For documents
 <div>
        <ul>
            @foreach($page->getDocuments('documents') as $document)
                <li>
                    <a href="{{ $document->getUrl() }}" target="_blank">
                        {{ $document->getCustomProperty('title') }}
                    </a>
                </li>
            @endforeach
        </ul>
 </div>

```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](https://github.com/webplusmultimedia/filament-json-media/security/policy) on how to report security vulnerabilities.

## Credits

- [webplusm](https://github.com/webplusmultimedia)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

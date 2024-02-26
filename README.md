# A Gallery media (Json) for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/daniel-ramesay/filament-json-media.svg?style=flat-square)](https://packagist.org/packages/daniel-ramesay/filament-json-media)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/daniel-ramesay/filament-json-media/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/daniel-ramesay/filament-json-media/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/daniel-ramesay/filament-json-media/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/daniel-ramesay/filament-json-media/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/daniel-ramesay/filament-json-media.svg?style=flat-square)](https://packagist.org/packages/daniel-ramesay/filament-json-media)



This package add a gallery images/documents for filament V3.x and fluents api for front-end in Laravel to display images, responsive images, url link for documents ...

## Installation

You can install the package via composer:

```bash
composer require webplusm/filament-json-media
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="webplusm-json-media-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="webplusm-json-media-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filamentJsonMedia = new GalleryJsonMedia\FilamentJsonMedia();
echo $filamentJsonMedia->echoPhrase('Hello, GalleryJsonMedia!');
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

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [webplusm](https://github.com/webplusmultimedia)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

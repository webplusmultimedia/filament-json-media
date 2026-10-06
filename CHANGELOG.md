# Changelog

All notable changes to `filament-json-media` will be documented in this file.

## v5.3.0 - 2026-10-06
* New `AsJsonMedia` cast: a json media field is read as a collection of `Media` (images) and `Document` (other files), the json stored in the database does not change
* New `<x-gallery-json-media::image>` Blade component: a lazy `<img>` with the thumbnail of the requested size, with an optional `format` (`format="webp"`)
* New `<x-gallery-json-media::responsive-image>` Blade component: a `srcset` of thumbnails in several widths and a `<picture>` with WebP sources (AVIF optional)
* New `images.responsive` config (`widths`, `formats`), `Media::getSrcset()` and a `format` argument for `Media::getCropUrl()`
* Security: the thumbnail urls of local images are signed with Laravel signed urls instead of a md5 token, the `signing_key` config is removed
* Fix: local thumbnails are generated on the disk of their image instead of the default disk of the package
* CI on PHP 8.3 and 8.4 with Laravel 12 and 13
* Upgrade: thumbnail urls with the former `_token` return 403, regenerate the pages you cache (HTML, CDN) to get the new signed urls
* Full Changelog: [v5.2.0...v5.3.0](https://github.com/webplusmultimedia/filament-json-media/compare/v5.2.0...v5.3.0)

## v5.2.0 - 2026-10-05
* Store medias on any disk (S3 and compatible), publicly or privately
* New `visibility` config and `->visibility()` support on `JsonMediaGallery`
* Private files are linked with temporary urls (`images.temporary_url_ttl`)
* Thumbnails of remote or private images are generated through a signed route
* Fix thumbnails of private images stored on a local disk
* Form previews only show the files of the record
* Full Changelog: [v5.1.0...v5.2.0](https://github.com/webplusmultimedia/filament-json-media/compare/v5.1.0...v5.2.0)

## v5.1.0 - 2026-10-05
* Security: validate the accepted file types and sizes of uploaded files on the server
* Security: only accept and delete the files the record already has (crafted requests could delete any file of the disk)
* Security: escape the `alt` and `src` attributes of `JsonMediaColumn` avatars
* Fix: keep the files of a soft deleted model until it is force deleted
* Fix: `Document::delete()` crashed, so deleting a model with documents threw an error
* Fix: `Croppa::reset()` could delete other images sharing the same name prefix
* Require Laravel 11.28+ (Filament 5) and `spatie/image` 3
* Test suite with Pest 4, CI on PHP 8.3 to 8.5 and Laravel 11 to 13 in [#34](https://github.com/webplusmultimedia/filament-json-media/pull/34)
* Full Changelog: [v5.0.1...v5.1.0](https://github.com/webplusmultimedia/filament-json-media/compare/v5.0.1...v5.1.0)

## v2.2.0 - 2024-04-25
* Feature by @webplusmultimedia in  https://github.com/webplusmultimedia/filament-json-media/pull/11
* Full Changelog: [v2.1.2...v2.2.0](https://github.com/webplusmultimedia/filament-json-media/compare/v2.1.2...v2.2.0)

## v2.1.0 - 2024-04-17
* Add support L11 by @webplusmultimedia in https://github.com/webplusmultimedia/filament-json-media/pull/8

**Full Changelog**: https://github.com/webplusmultimedia/filament-json-media/compare/v2.0.0.0...v2.1.0

## v2.0.0 - 2024-04-16
* Feature with spatie/image by @webplusmultimedia in https://github.com/webplusmultimedia/filament-json-media/pull/7

**Full Changelog**: https://github.com/webplusmultimedia/filament-json-media/compare/v1.2.03...v2.0.0.0

## v1.2.02 - 2024-03-21

**Full Changelog**: https://github.com/webplusmultimedia/filament-json-media/compare/v1.2.01...v1.2.02

## fix padding column table - 2024-03-21

fix padding column table

## fix namespace - 2024-03-03

**Full Changelog**: https://github.com/webplusmultimedia/filament-json-media/compare/v1.1.0...v1.2.0
Fix namespace and some name class

## JsonMediaEntry for Infolist - 2024-03-02

**Full Changelog**: https://github.com/webplusmultimedia/filament-json-media/compare/v1.0.1...v1.1.0

## can upload documents - 2024-02-28

**Full Changelog**: https://github.com/webplusmultimedia/filament-json-media/compare/v1.0.0...v1.0.1

## form field + column json media gallery - 2024-02-26

add: filament form field + column json media gallery

## 1.0.0 - 202X-XX-XX

- initial release

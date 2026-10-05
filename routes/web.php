<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\Controllers\JsonImageController;
use GalleryJsonMedia\JsonMedia\Controllers\RemoteThumbnailController;
use GalleryJsonMedia\JsonMedia\UrlParser;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Route;

Route::get('gallery-json-media/thumbnails/{disk}/{path}', RemoteThumbnailController::class)
    ->where('path', '.+')
    ->middleware(ValidateSignature::class)
    ->name('gallery-json-media.thumbnail');

// Reached only when the thumbnail file does not exist yet : the web server serves it otherwise.
// The signature is relative, so that it does not depend on the domain (CDN, several domains)
Route::get('{path}', [JsonImageController::class, 'handle'])
    ->where('path', UrlParser::make()->routePattern())
    ->middleware(ValidateSignature::relative())
    ->name('gallery-json-media.local-thumbnail');

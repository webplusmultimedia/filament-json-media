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

Route::get('{path}', [JsonImageController::class, 'handle'])
    ->where('path', UrlParser::make()->routePattern());

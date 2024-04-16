<?php

declare(strict_types=1);

use GalleryJsonMedia\JsonMedia\Controllers\JsonImageController;
use GalleryJsonMedia\JsonMedia\UrlParser;
use Illuminate\Support\Facades\Route;

Route::get('{path}', [JsonImageController::class, 'handle'])
    ->where('path', UrlParser::make()->routePattern());

<?php

namespace WebplusMultimedia\GalleryJsonMedia\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \WebplusMultimedia\GalleryJsonMedia\GalleryJsonMedia
 */
class GalleryJsonMedia extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \WebplusMultimedia\GalleryJsonMedia\GalleryJsonMedia::class;
    }
}

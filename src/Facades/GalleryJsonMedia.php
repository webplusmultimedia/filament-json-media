<?php

namespace WebplusMultimedia\GalleryJsonMedia\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \WebplusMultimedia\GalleryJsonMedia\Form\GalleryJsonMedia
 */
class GalleryJsonMedia extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \WebplusMultimedia\GalleryJsonMedia\Form\GalleryJsonMedia::class;
    }
}

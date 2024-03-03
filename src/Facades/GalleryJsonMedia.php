<?php

namespace GalleryJsonMedia\Facades;

use GalleryJsonMedia\Form\JsonMediaGallery;
use Illuminate\Support\Facades\Facade;

/**
 * @see \GalleryJsonMedia\Form\JsonMediaGallery
 */
class GalleryJsonMedia extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return JsonMediaGallery::class;
    }
}

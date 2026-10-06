<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Tests\Fixtures\Models;

use GalleryJsonMedia\JsonMedia\Casts\AsJsonMedia;

class JsonMediaCastPage extends Page
{
    protected function casts(): array
    {
        return [
            'images' => AsJsonMedia::class,
            'documents' => AsJsonMedia::class,
        ];
    }
}

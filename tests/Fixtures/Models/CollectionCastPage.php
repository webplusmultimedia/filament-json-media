<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;

class CollectionCastPage extends Page
{
    protected function casts(): array
    {
        return [
            'images' => AsCollection::class,
            'documents' => AsCollection::class,
        ];
    }
}

<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Tests\Fixtures\Models;

use GalleryJsonMedia\JsonMedia\Concerns\InteractWithMedia;
use GalleryJsonMedia\JsonMedia\Contracts\HasMedia;
use Illuminate\Database\Eloquent\Model;

class Page extends Model implements HasMedia
{
    use InteractWithMedia;

    protected $table = 'pages';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'documents' => 'array',
        ];
    }

    protected function getFieldsToDeleteMedia(): array
    {
        return ['images', 'documents'];
    }
}

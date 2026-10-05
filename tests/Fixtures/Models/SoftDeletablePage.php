<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SoftDeletablePage extends Page
{
    use SoftDeletes;
}

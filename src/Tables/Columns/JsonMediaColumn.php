<?php

namespace GalleryJsonMedia\Tables\Columns;

use Filament\Tables\Columns\Column;
use GalleryJsonMedia\Support\Concerns\HasAvatars;
use GalleryJsonMedia\Support\Concerns\HasThumbProperties;

class JsonMediaColumn extends Column
{
    use HasAvatars;
    use HasThumbProperties;

    protected string $view = 'gallery-json-media::table.gallery-column';

    protected function setUp(): void
    {
        parent::setUp();
        $this->thumbWidth(40);
        $this->thumbHeight(40);
    }
}

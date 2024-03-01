<?php

namespace WebplusMultimedia\GalleryJsonMedia\Tables\Columns;

use Filament\Tables\Columns\Column;
use WebplusMultimedia\GalleryJsonMedia\Form\Concerns\HasThumbProperties;
use WebplusMultimedia\GalleryJsonMedia\Tables\Columns\Concerns\HasAvatars;

class GalleryJsonMediaColumn extends Column
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

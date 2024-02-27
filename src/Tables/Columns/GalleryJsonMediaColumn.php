<?php

namespace WebplusMultimedia\GalleryJsonMedia\Tables\Columns;

use Filament\Tables\Columns\Column;
use WebplusMultimedia\GalleryJsonMedia\Form\Concerns\HasThumbProperties;

class GalleryJsonMediaColumn extends Column
{
    use HasThumbProperties;

    protected string $view = 'gallery-json-media::table.gallery-column';

    protected function setUp(): void
    {
        parent::setUp();
        $this->thumbWidth(40);
        $this->thumbHeight(40);
    }
}
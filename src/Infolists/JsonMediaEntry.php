<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Infolists;

use Filament\Infolists\Components\Entry;
use GalleryJsonMedia\Support\Concerns\HasAvatars;
use GalleryJsonMedia\Support\Concerns\HasThumbProperties;

class JsonMediaEntry extends Entry
{
    use HasAvatars;
    use HasThumbProperties;

    protected string $view = 'gallery-json-media::infolist.json-media';
}

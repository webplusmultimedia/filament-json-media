<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Enums;

enum GalleryType: string
{
    case Image = 'image';
    case Document = 'document';
}

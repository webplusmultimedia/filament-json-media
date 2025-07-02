<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 *
 * @category    Category
 *
 * @author      daniel
 *
 * @link        http://webplusm.net
 * Date: 24/04/2024 18:22
 */

namespace GalleryJsonMedia\Infolists;

use Filament\Infolists\Components\Entry;

class JsonDocumentsEntry extends Entry
{
    protected string $view = 'gallery-json-media::infolist.json-documents-entry';

    protected function setUp(): void
    {
        parent::setUp();
    }
}

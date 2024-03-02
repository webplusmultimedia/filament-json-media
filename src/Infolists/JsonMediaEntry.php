<?php
/**
 * Created by PhpStorm.
 *
 * @category    Category
 *
 * @author      daniel
 *
 * @link        http://webplusm.net
 * Date: 01/03/2024 19:32
 */

namespace WebplusMultimedia\GalleryJsonMedia\Infolists;

use Filament\Infolists\Components\Entry;
use WebplusMultimedia\GalleryJsonMedia\Form\Concerns\HasThumbProperties;
use WebplusMultimedia\GalleryJsonMedia\Tables\Columns\Concerns\HasAvatars;

class JsonMediaEntry extends Entry
{
    use HasAvatars;
    use HasThumbProperties;

    protected string $view = 'gallery-json-media::infolist.json-media';

    protected function setUp(): void
    {
        parent::setUp();

    }
}

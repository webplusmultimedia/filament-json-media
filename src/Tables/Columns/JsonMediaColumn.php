<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Tables\Columns;

use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Tables\Columns\Column;
use GalleryJsonMedia\JsonMedia\Contracts\HasMedia;
use GalleryJsonMedia\Support\Concerns\HasAvatars;
use GalleryJsonMedia\Support\Concerns\HasThumbProperties;

class JsonMediaColumn extends Column implements HasEmbeddedView
{
    use HasAvatars;
    use HasThumbProperties;

    protected function setUp(): void
    {
        parent::setUp();
        $this->thumbWidth(40);
        $this->thumbHeight(40);
    }

    public function toEmbeddedHtml(): string
    {
        $record = $this->getRecord();
        if (! $record instanceof HasMedia) {
            return '<span class="text-xs text-warning-500">HasMedia interface not implemented on the record model.</span>';
        }
        ob_start(); ?>
        <div class="flex gap-x-1 items-center max-w-max">
            <?php if (! $record->hasDocuments($this->getName())) { ?>
                <?php if (! $this->hasAvatars()) {
                    $record->getFirstMedia($this->getName())?->withImageProperties($this->getThumbWidth(), $this->getThumbHeight());
                } else { ?>
                <div class="flex  -space-x-5 p-2 overflow-hidden" style="max-height: <?= $this->getThumbHeight() + 16 ?>px">
                    <?php foreach (collect($record->getMedias($this->getName()))->take($this->getMaxAvatars())->all() as $media) { ?>
                        <img class="inline-block rounded-full ring-2 ring-gray-100 dark:ring-gray-100 object-cover"
                             src="<?= $media->getCropUrl($this->getThumbWidth(), $this->getThumbHeight()) ?>"
                             alt="<?= $media->getCustomProperty('alt') ?>" width="<?= $this->getThumbWidth() ?>"
                             height="<?= $this->getThumbHeight() ?>"
                             loading="lazy"
                        />
                    <?php } ?>
                </div>
                    <?php if (($nb = ($record->mediasCount($this->getName()) - collect($record->getMedias($this->getName()))->take($this->getMaxAvatars())->count())) > 0) { ?>
                            <?= view('filament::components.badge', ['color' => 'info', 'size' => 'xs', 'slot' => '+' . $nb])->toHtml() ?>
                   <?php } ?>
                <?php } ?>
            <?php } ?>
        </div>
    <?php return ob_get_clean();
    }
}

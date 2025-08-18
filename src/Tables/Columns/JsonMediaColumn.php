<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Tables\Columns;

use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Tables\Columns\Column;
use GalleryJsonMedia\Form\Concerns\HasRing;
use GalleryJsonMedia\JsonMedia\Contracts\HasMedia;
use GalleryJsonMedia\Support\Concerns\HasAvatars;
use GalleryJsonMedia\Support\Concerns\HasThumbProperties;

class JsonMediaColumn extends Column implements HasEmbeddedView
{
    use HasAvatars;
    use HasRing;
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
        $attributes = $this->getExtraAttributeBag()
            ->class([
                'wm-image ring-white dark:ring-gray-900 inline-block rounded-full object-cover',
                "{$this->getRing()}",
            ]);
        ob_start(); ?>
        <div class="flex gap-x-1 items-center max-w-max">
            <?php if (! $record->hasDocuments($this->getName())) { ?>
                <?php if (! $this->hasAvatars()) {
                    $record->getFirstMedia($this->getName())?->withImageProperties($this->getThumbWidth(), $this->getThumbHeight());
                } else { ?>
                <div class="flex -space-x-5 overflow-hidden" style="max-height: <?= $this->getThumbHeight() + 16 ?>px">
                    <?php foreach (collect($record->getMedias($this->getName()))->take($this->getMaxAvatars())->all() as $media) { ?>
                        <img <?= $attributes->toHtml() ?>
                             src="<?= $media->getCropUrl($this->getThumbWidth(), $this->getThumbHeight()) ?>"
                             alt="<?= $media->getCustomProperty('alt') ?>"
                             width="<?= $this->getThumbWidth() ?>"
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

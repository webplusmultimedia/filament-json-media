<?php
/**
 * Created by PhpStorm.
 *
 * @category    Category
 *
 * @author      daniel
 *
 * @link        http://webplusm.net
 * Date: 19/02/2024 22:51
 */

namespace WebplusMultimedia\GalleryJsonMedia\Form\Concerns;

use Closure;

trait HasThumbProperties
{
    protected int | Closure $thumbWidth = 300;

    protected int | Closure $thumbHeight = 230;

    public function thumbWidth(int | Closure $thumbWidth): static
    {
        $this->thumbWidth = $thumbWidth;

        return $this;
    }

    public function thumbHeight(int | Closure $thumbHeight): static
    {
        $this->thumbHeight = $thumbHeight;

        return $this;
    }

    public function getThumbWidth(): int
    {
        return $this->evaluate($this->thumbWidth);
    }

    public function getThumbHeight(): int
    {
        return $this->evaluate($this->thumbHeight);
    }
}

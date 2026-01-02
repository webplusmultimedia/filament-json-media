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
 * Date: 19/02/2024 22:51
 */

namespace GalleryJsonMedia\Support\Concerns;

use Closure;
use GalleryJsonMedia\Enums\DisplayOnEnum;

trait HasThumbProperties
{
    protected int | Closure $thumbWidth = 300;

    protected int | Closure $thumbHeight = 230;

    protected DisplayOnEnum | Closure $displayOn = DisplayOnEnum::LIST;

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

    public function getDisplayOn(): DisplayOnEnum
    {
        return $this->evaluate($this->displayOn);
    }

    public function displayOnGrid(): static
    {
        return $this->displayOn(DisplayOnEnum::GRID);
    }

    public function displayOnList(): static
    {
        return $this->displayOn(DisplayOnEnum::LIST);
    }

    public function displayOn(DisplayOnEnum | Closure $displayOn): static
    {
        $this->displayOn = $displayOn;

        return $this;
    }
}

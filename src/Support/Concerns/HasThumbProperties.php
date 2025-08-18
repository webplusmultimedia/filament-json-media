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

    protected ?int $ring = null;

    /** @param int<1,4> $ring */
    public function ring(int $ring): self
    {
        $this->ring = $ring;

        return $this;
    }

    public function getRing(): string
    {
        if ($this->ring === null) {
            $this->ring = 2; // Default ring value
        }

        return match ($this->ring) {
            1 => 'ring-1',
            2 => 'ring-2',
            3 => 'ring-3',
            4 => 'ring-4',
            default => 'ring-2', // Fallback to medium ring
        };
    }
}

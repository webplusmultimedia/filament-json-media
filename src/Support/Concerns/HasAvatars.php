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
 * Date: 01/03/2024 17:43
 */

namespace GalleryJsonMedia\Support\Concerns;

trait HasAvatars
{
    protected bool | \Closure $isAvatars = false;

    protected int | \Closure $maxAvatar = 4;

    public function hasAvatars(): bool
    {
        return $this->evaluate($this->isAvatars);
    }

    public function avatars(bool | \Closure $isAvatars = true): static
    {
        $this->isAvatars = $isAvatars;

        return $this;
    }

    public function maxAvatar(int | \Closure $maxAvatar = 4): static
    {
        $this->maxAvatar = $maxAvatar;

        return $this;
    }

    public function getMaxAvatars(): int
    {
        return $this->evaluate($this->maxAvatar);
    }
}

<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Form\Concerns;

use Closure;

trait CanHorizontalMouseScrolling
{
    protected bool | Closure $horizontalMouseScrolling = false;

    public function horizontalMouseScrolling(bool | Closure $condition = true): static
    {
        $this->horizontalMouseScrolling = $condition;

        return $this;
    }

    public function hasHorizontalMouseScrolling(): bool
    {
        return $this->evaluate($this->horizontalMouseScrolling);
    }
}

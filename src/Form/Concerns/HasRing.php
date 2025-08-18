<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Form\Concerns;

trait HasRing
{
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
